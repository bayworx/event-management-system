<?php

namespace App\Repository;

use App\Entity\Administrator;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Administrator>
 */
class AdministratorRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Administrator::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Administrator) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find active administrators
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['name' => 'ASC']);
    }

    /**
     * Find administrators who can manage a specific event
     */
    public function findEventManagers(Event $event): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.managedEvents', 'e')
            ->where('a.isActive = true')
            ->andWhere('a.isSuperAdmin = true OR e = :event')
            ->setParameter('event', $event)
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find super administrators
     */
    public function findSuperAdmins(): array
    {
        return $this->findBy(['isSuperAdmin' => true, 'isActive' => true], ['name' => 'ASC']);
    }

    /**
     * Find administrators by department
     */
    public function findByDepartment(string $department): array
    {
        return $this->findBy(['department' => $department, 'isActive' => true], ['name' => 'ASC']);
    }

    /**
     * Search administrators by name or email
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.name LIKE :query OR a.email LIKE :query')
            ->andWhere('a.isActive = true')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find administrators with recent login activity
     */
    public function findRecentlyActive(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.lastLoginAt >= :since')
            ->andWhere('a.isActive = true')
            ->setParameter('since', $since)
            ->orderBy('a.lastLoginAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count super administrators
     */
    public function countSuperAdmins(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.isSuperAdmin = true')
            ->andWhere('a.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all unique departments
     */
    public function findAllDepartments(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('DISTINCT a.department')
            ->where('a.department IS NOT NULL')
            ->andWhere('a.department != :empty')
            ->setParameter('empty', '')
            ->orderBy('a.department', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'department');
    }
}
