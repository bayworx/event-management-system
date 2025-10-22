<?php

namespace App\Repository;

use App\Entity\EventImport;
use App\Entity\Administrator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventImport>
 *
 * @method EventImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventImport[]    findAll()
 * @method EventImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventImport::class);
    }

    public function save(EventImport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventImport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find imports for an administrator
     */
    public function findForAdministrator(Administrator $admin): Query
    {
        return $this->createQueryBuilder('ei')
            ->where('ei.createdBy = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('ei.createdAt', 'DESC')
            ->getQuery();
    }

    /**
     * Find recent imports
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('ei')
            ->leftJoin('ei.createdBy', 'cb')
            ->addSelect('cb')
            ->orderBy('ei.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find imports by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ei')
            ->where('ei.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ei.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get import statistics
     */
    public function getImportStats(): array
    {
        $result = $this->createQueryBuilder('ei')
            ->select('
                COUNT(ei.id) as total_imports,
                SUM(CASE WHEN ei.status = \'completed\' THEN 1 ELSE 0 END) as completed_imports,
                SUM(CASE WHEN ei.status = \'failed\' THEN 1 ELSE 0 END) as failed_imports,
                SUM(CASE WHEN ei.status = \'processing\' THEN 1 ELSE 0 END) as processing_imports,
                SUM(ei.successfulRows) as total_successful_rows,
                SUM(ei.failedRows) as total_failed_rows
            ')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['total_imports'],
            'completed' => (int) $result['completed_imports'],
            'failed' => (int) $result['failed_imports'],
            'processing' => (int) $result['processing_imports'],
            'successful_rows' => (int) $result['total_successful_rows'],
            'failed_rows' => (int) $result['total_failed_rows']
        ];
    }

    /**
     * Clean up old imports (older than specified days)
     */
    public function cleanupOldImports(int $daysOld = 30): int
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$daysOld} days");

        return $this->createQueryBuilder('ei')
            ->delete()
            ->where('ei.createdAt < :cutoff')
            ->andWhere('ei.status IN (:statuses)')
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('statuses', ['completed', 'failed', 'cancelled'])
            ->getQuery()
            ->execute();
    }
}