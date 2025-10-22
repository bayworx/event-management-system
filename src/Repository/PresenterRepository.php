<?php

namespace App\Repository;

use App\Entity\Presenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Presenter>
 *
 * @method Presenter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Presenter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Presenter[]    findAll()
 * @method Presenter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresenterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presenter::class);
    }

    /**
     * Find presenters by search term (name, email, or company)
     */
    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :searchTerm')
            ->orWhere('p.email LIKE :searchTerm')
            ->orWhere('p.company LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all presenters ordered by name
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find presenters not yet assigned to a specific event
     */
    public function findAvailableForEvent(int $eventId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.eventPresenters', 'ep')
            ->leftJoin('ep.event', 'e')
            ->where('e.id != :eventId OR e.id IS NULL')
            ->setParameter('eventId', $eventId)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}