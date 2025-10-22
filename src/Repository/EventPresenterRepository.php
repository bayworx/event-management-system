<?php

namespace App\Repository;

use App\Entity\EventPresenter;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventPresenter>
 *
 * @method EventPresenter|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventPresenter|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventPresenter[]    findAll()
 * @method EventPresenter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventPresenterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventPresenter::class);
    }

    /**
     * Find visible presenters for an event, ordered by sort order
     */
    public function findVisibleForEvent(Event $event): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.presenter', 'p')
            ->where('ep.event = :event')
            ->andWhere('ep.isVisible = :visible')
            ->setParameter('event', $event)
            ->setParameter('visible', true)
            ->orderBy('ep.sortOrder', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all presenters for an event (including hidden ones), ordered by sort order
     */
    public function findAllForEvent(Event $event): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.presenter', 'p')
            ->where('ep.event = :event')
            ->setParameter('event', $event)
            ->orderBy('ep.sortOrder', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find presenters scheduled for a specific time range
     */
    public function findByTimeRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.presenter', 'p')
            ->join('ep.event', 'e')
            ->where('ep.startTime IS NOT NULL')
            ->andWhere('ep.endTime IS NOT NULL')
            ->andWhere('ep.startTime >= :start')
            ->andWhere('ep.endTime <= :end')
            ->andWhere('ep.isVisible = :visible')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('visible', true)
            ->orderBy('ep.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the maximum sort order for an event (to add new presenters at the end)
     */
    public function getMaxSortOrderForEvent(Event $event): int
    {
        $result = $this->createQueryBuilder('ep')
            ->select('MAX(ep.sortOrder) as maxOrder')
            ->where('ep.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}