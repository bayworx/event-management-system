<?php

namespace App\Repository;

use App\Entity\FeaturedEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeaturedEvent>
 */
class FeaturedEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeaturedEvent::class);
    }

    /**
     * Find all active featured events ordered by priority
     */
    public function findActiveFeatures(?string $displayType = null): array
    {
        $qb = $this->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->addSelect('e')
            ->where('fe.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('fe.priority', 'DESC')
            ->addOrderBy('fe.createdAt', 'DESC');

        if ($displayType) {
            $qb->andWhere('fe.displayType = :displayType')
               ->setParameter('displayType', $displayType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find currently active featured events (within date range)
     */
    public function findCurrentlyActive(?string $displayType = null): array
    {
        $now = new \DateTime();
        
        $qb = $this->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->addSelect('e')
            ->where('fe.isActive = :active')
            ->andWhere('(fe.startDate IS NULL OR fe.startDate <= :now)')
            ->andWhere('(fe.endDate IS NULL OR fe.endDate >= :now)')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('fe.priority', 'DESC')
            ->addOrderBy('fe.createdAt', 'DESC');

        if ($displayType) {
            $qb->andWhere('fe.displayType = :displayType')
               ->setParameter('displayType', $displayType);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find featured events for rotation (banner type, currently active)
     */
    public function findForRotation(int $limit = 5): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->addSelect('e')
            ->where('fe.isActive = :active')
            ->andWhere('fe.displayType = :displayType')
            ->andWhere('(fe.startDate IS NULL OR fe.startDate <= :now)')
            ->andWhere('(fe.endDate IS NULL OR fe.endDate >= :now)')
            ->setParameter('active', true)
            ->setParameter('displayType', 'banner')
            ->setParameter('now', $now)
            ->orderBy('fe.priority', 'DESC')
            ->addOrderBy('fe.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured events by related event
     */
    public function findByRelatedEvent(int $eventId): array
    {
        return $this->createQueryBuilder('fe')
            ->where('fe.relatedEvent = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('fe.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get featured events statistics
     */
    public function getStatistics(): array
    {
        $totalActive = $this->createQueryBuilder('fe')
            ->select('COUNT(fe.id)')
            ->where('fe.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalViews = $this->createQueryBuilder('fe')
            ->select('SUM(fe.viewCount)')
            ->where('fe.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalClicks = $this->createQueryBuilder('fe')
            ->select('SUM(fe.clickCount)')
            ->where('fe.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $averageCTR = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        return [
            'totalActive' => (int) $totalActive,
            'totalViews' => (int) $totalViews,
            'totalClicks' => (int) $totalClicks,
            'averageCTR' => $averageCTR
        ];
    }

    /**
     * Find top performing featured events
     */
    public function findTopPerforming(int $limit = 10): array
    {
        // First get the data, then sort by calculated CTR in PHP
        $results = $this->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->addSelect('e')
            ->where('fe.viewCount > 0')
            ->orderBy('fe.viewCount', 'DESC')
            ->addOrderBy('fe.clickCount', 'DESC')
            ->getQuery()
            ->getResult();

        // Sort by CTR (Click Through Rate) in PHP
        usort($results, function($a, $b) {
            $ctrA = $a->getViewCount() > 0 ? ($a->getClickCount() / $a->getViewCount()) : 0;
            $ctrB = $b->getViewCount() > 0 ? ($b->getClickCount() / $b->getViewCount()) : 0;
            
            if ($ctrA === $ctrB) {
                return $b->getViewCount() <=> $a->getViewCount(); // Sort by views descending if CTR is equal
            }
            
            return $ctrB <=> $ctrA; // Sort by CTR descending
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Find expiring featured events (ending within next 7 days)
     */
    public function findExpiringSoon(): array
    {
        $now = new \DateTime();
        $weekFromNow = (new \DateTime())->add(new \DateInterval('P7D'));

        return $this->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->addSelect('e')
            ->where('fe.isActive = :active')
            ->andWhere('fe.endDate IS NOT NULL')
            ->andWhere('fe.endDate BETWEEN :now AND :weekFromNow')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('weekFromNow', $weekFromNow)
            ->orderBy('fe.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Clean up expired featured events
     */
    public function deactivateExpired(): int
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('fe')
            ->update()
            ->set('fe.isActive', ':inactive')
            ->where('fe.endDate < :now')
            ->andWhere('fe.isActive = :active')
            ->setParameter('inactive', false)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}