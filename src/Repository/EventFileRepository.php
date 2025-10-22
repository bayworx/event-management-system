<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventFile>
 */
class EventFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventFile::class);
    }

    /**
     * Find active files for an event, ordered by sort order
     */
    public function findActiveByEvent(Event $event): array
    {
        return $this->createQueryBuilder('ef')
            ->where('ef.event = :event')
            ->andWhere('ef.isActive = true')
            ->setParameter('event', $event)
            ->orderBy('ef.sortOrder', 'ASC')
            ->addOrderBy('ef.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all files for an event (including inactive)
     */
    public function findAllByEvent(Event $event): array
    {
        return $this->createQueryBuilder('ef')
            ->where('ef.event = :event')
            ->setParameter('event', $event)
            ->orderBy('ef.sortOrder', 'ASC')
            ->addOrderBy('ef.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find files by mime type for an event
     */
    public function findByMimeType(Event $event, string $mimeType): array
    {
        return $this->createQueryBuilder('ef')
            ->where('ef.event = :event')
            ->andWhere('ef.mimeType LIKE :mimeType')
            ->andWhere('ef.isActive = true')
            ->setParameter('event', $event)
            ->setParameter('mimeType', $mimeType . '%')
            ->orderBy('ef.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find most downloaded files for an event
     */
    public function findMostDownloaded(Event $event, int $limit = 5): array
    {
        return $this->createQueryBuilder('ef')
            ->where('ef.event = :event')
            ->andWhere('ef.isActive = true')
            ->andWhere('ef.downloadCount > 0')
            ->setParameter('event', $event)
            ->orderBy('ef.downloadCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently uploaded files
     */
    public function findRecentlyUploaded(Event $event = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('ef')
            ->where('ef.isActive = true')
            ->orderBy('ef.uploadedAt', 'DESC')
            ->setMaxResults($limit);

        if ($event) {
            $qb->andWhere('ef.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get total file size for an event
     */
    public function getTotalSizeByEvent(Event $event): int
    {
        $result = $this->createQueryBuilder('ef')
            ->select('SUM(ef.fileSize)')
            ->where('ef.event = :event')
            ->andWhere('ef.isActive = true')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?: 0);
    }

    /**
     * Search files by name or description
     */
    public function search(string $query, Event $event = null): array
    {
        $qb = $this->createQueryBuilder('ef')
            ->where('ef.name LIKE :query OR ef.description LIKE :query OR ef.originalName LIKE :query')
            ->andWhere('ef.isActive = true')
            ->setParameter('query', '%' . $query . '%');

        if ($event) {
            $qb->andWhere('ef.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->orderBy('ef.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Update sort orders for files in bulk
     */
    public function updateSortOrders(array $fileIds, array $sortOrders): void
    {
        $em = $this->getEntityManager();
        
        foreach ($fileIds as $index => $fileId) {
            $file = $this->find($fileId);
            if ($file && isset($sortOrders[$index])) {
                $file->setSortOrder((int) $sortOrders[$index]);
                $em->persist($file);
            }
        }
        
        $em->flush();
    }
}