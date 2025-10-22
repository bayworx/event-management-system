<?php

namespace App\Repository;

use App\Entity\Administrator;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find active events
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['startDate' => 'ASC']);
    }

    /**
     * Find event by slug
     */
    public function findOneBySlug(string $slug): ?Event
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find upcoming events
     */
    public function findUpcoming(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.startDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find past events
     */
    public function findPast(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.startDate < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find events happening today
     */
    public function findToday(): array
    {
        $today = new \DateTime();
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.startDate >= :today')
            ->andWhere('e.startDate < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events managed by administrator
     */
    public function findManagedByAdministrator(Administrator $administrator): array
    {
        if ($administrator->isSuperAdmin()) {
            return $this->findActive();
        }

        return $this->createQueryBuilder('e')
            ->leftJoin('e.administrators', 'a')
            ->where('e.isActive = true')
            ->andWhere('a = :administrator')
            ->setParameter('administrator', $administrator)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title, description, or location
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
            ->andWhere('e.isActive = true')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events within a date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.startDate >= :startDate')
            ->andWhere('e.startDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get events statistics
     */
    public function getStatistics(): array
    {
        $totalEvents = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();

        $upcomingEvents = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.isActive = true')
            ->andWhere('e.startDate >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        $pastEvents = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.isActive = true')
            ->andWhere('e.startDate < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $totalEvents,
            'upcoming' => (int) $upcomingEvents,
            'past' => (int) $pastEvents,
        ];
    }
}
