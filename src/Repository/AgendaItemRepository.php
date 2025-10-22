<?php

namespace App\Repository;

use App\Entity\AgendaItem;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgendaItem>
 *
 * @method AgendaItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgendaItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgendaItem[]    findAll()
 * @method AgendaItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgendaItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgendaItem::class);
    }

    /**
     * Find all agenda items for an event, ordered by start time and sort order
     */
    public function findByEvent(Event $event, bool $visibleOnly = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->setParameter('event', $event)
            ->orderBy('a.startTime', 'ASC')
            ->addOrderBy('a.sortOrder', 'ASC');

        if ($visibleOnly) {
            $qb->andWhere('a.isVisible = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find visible agenda items for an event (public display)
     */
    public function findVisibleByEvent(Event $event): array
    {
        return $this->findByEvent($event, true);
    }

    /**
     * Find agenda items by event and item type
     */
    public function findByEventAndType(Event $event, string $itemType): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->andWhere('a.itemType = :itemType')
            ->setParameter('event', $event)
            ->setParameter('itemType', $itemType)
            ->orderBy('a.startTime', 'ASC')
            ->addOrderBy('a.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find agenda items scheduled for a specific date
     */
    public function findByEventAndDate(Event $event, \DateTimeInterface $date): array
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->andWhere('a.startTime BETWEEN :startOfDay AND :endOfDay')
            ->setParameter('event', $event)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->orderBy('a.startTime', 'ASC')
            ->addOrderBy('a.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find next agenda item after current time
     */
    public function findNextItem(Event $event, \DateTimeInterface $currentTime = null): ?AgendaItem
    {
        if ($currentTime === null) {
            $currentTime = new \DateTime();
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->andWhere('a.startTime > :currentTime')
            ->andWhere('a.isVisible = true')
            ->setParameter('event', $event)
            ->setParameter('currentTime', $currentTime)
            ->orderBy('a.startTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find current agenda item (happening now)
     */
    public function findCurrentItem(Event $event, \DateTimeInterface $currentTime = null): ?AgendaItem
    {
        if ($currentTime === null) {
            $currentTime = new \DateTime();
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->andWhere('a.startTime <= :currentTime')
            ->andWhere('(a.endTime IS NULL OR a.endTime >= :currentTime)')
            ->andWhere('a.isVisible = true')
            ->setParameter('event', $event)
            ->setParameter('currentTime', $currentTime)
            ->orderBy('a.startTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get agenda items grouped by date
     */
    public function findGroupedByDate(Event $event, bool $visibleOnly = false): array
    {
        $items = $this->findByEvent($event, $visibleOnly);
        $grouped = [];

        foreach ($items as $item) {
            $dateKey = $item->getStartTime()->format('Y-m-d');
            $grouped[$dateKey][] = $item;
        }

        return $grouped;
    }

    /**
     * Count agenda items for an event
     */
    public function countByEvent(Event $event): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find agenda items by presenter
     */
    public function findByPresenter($presenter): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.presenter = :presenter')
            ->setParameter('presenter', $presenter)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the next sort order for an event
     */
    public function getNextSortOrder(Event $event): int
    {
        $result = $this->createQueryBuilder('a')
            ->select('MAX(a.sortOrder)')
            ->andWhere('a.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * Find overlapping agenda items
     */
    public function findOverlapping(
        Event $event, 
        \DateTimeInterface $startTime, 
        \DateTimeInterface $endTime = null, 
        AgendaItem $excludeItem = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.event = :event')
            ->setParameter('event', $event);

        if ($endTime) {
            $qb->andWhere('(a.startTime < :endTime AND (a.endTime IS NULL OR a.endTime > :startTime))')
                ->setParameter('startTime', $startTime)
                ->setParameter('endTime', $endTime);
        } else {
            $qb->andWhere('a.startTime = :startTime')
                ->setParameter('startTime', $startTime);
        }

        if ($excludeItem) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeItem->getId());
        }

        return $qb->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(AgendaItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgendaItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}