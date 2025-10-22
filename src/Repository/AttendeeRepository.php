<?php

namespace App\Repository;

use App\Entity\Attendee;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Attendee>
 */
class AttendeeRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendee::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Attendee) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find attendee by email verification token
     */
    public function findOneByEmailVerificationToken(string $token): ?Attendee
    {
        return $this->findOneBy(['emailVerificationToken' => $token]);
    }

    /**
     * Find attendees by event
     */
    public function findByEvent(Event $event, bool $onlyVerified = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.event = :event')
            ->setParameter('event', $event)
            ->orderBy('a.registeredAt', 'DESC');

        if ($onlyVerified) {
            $qb->andWhere('a.isVerified = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count attendees by event
     */
    public function countByEvent(Event $event, bool $onlyVerified = false): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.event = :event')
            ->setParameter('event', $event);

        if ($onlyVerified) {
            $qb->andWhere('a.isVerified = true');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find checked-in attendees for event
     */
    public function findCheckedInByEvent(Event $event): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.event = :event')
            ->andWhere('a.isCheckedIn = true')
            ->setParameter('event', $event)
            ->orderBy('a.checkedInAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find attendees registered in date range
     */
    public function findRegisteredBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.registeredAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.registeredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search attendees by name, email, or organization
     */
    public function search(string $query, ?Event $event = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.name LIKE :query OR a.email LIKE :query OR a.organization LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($event) {
            $qb->andWhere('a.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}