<?php

namespace App\Repository;

use App\Entity\Administrator;
use App\Entity\Attendee;
use App\Entity\Event;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find messages for an administrator with filtering and sorting
     */
    public function findForAdministrator(
        Administrator $admin,
        ?Event $event = null,
        ?string $status = null,
        bool $unreadOnly = false
    ): Query {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.event', 'e')
            ->where('m.recipient = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('m.isRead', 'ASC')
            ->addOrderBy('m.sentAt', 'DESC');

        if ($event) {
            $qb->andWhere('m.event = :event')
               ->setParameter('event', $event);
        }

        if ($status) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        }

        if ($unreadOnly) {
            $qb->andWhere('m.isRead = :isRead')
               ->setParameter('isRead', false);
        }

        return $qb->getQuery();
    }

    /**
     * Find messages sent by an attendee
     */
    public function findForAttendee(Attendee $attendee, ?Event $event = null): Query
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.recipient', 'r')
            ->leftJoin('m.event', 'e')
            ->where('m.sender = :attendee')
            ->setParameter('attendee', $attendee)
            ->orderBy('m.sentAt', 'DESC');

        if ($event) {
            $qb->andWhere('m.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->getQuery();
    }

    /**
     * Count unread messages for an administrator
     */
    public function countUnreadForAdmin(Administrator $admin, ?Event $event = null): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.recipient = :admin')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('admin', $admin)
            ->setParameter('isRead', false);

        if ($event) {
            $qb->andWhere('m.event = :event')
               ->setParameter('event', $event);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find conversation thread (original message and all replies)
     */
    public function findConversationThread(Message $originalMessage): array
    {
        // If this is already a reply, find the original message
        $original = $originalMessage->getReplyTo() ?? $originalMessage;

        return $this->createQueryBuilder('m')
            ->where('m.id = :originalId OR m.replyTo = :original')
            ->setParameter('originalId', $original->getId())
            ->setParameter('original', $original)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent messages for dashboard
     */
    public function findRecentForAdmin(Administrator $admin, int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->leftJoin('m.event', 'e')
            ->where('m.recipient = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark all messages as read for an administrator
     */
    public function markAllAsReadForAdmin(Administrator $admin, ?Event $event = null): int
    {
        $qb = $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', 'true')
            ->set('m.readAt', ':now')
            ->where('m.recipient = :admin')
            ->andWhere('m.isRead = false')
            ->setParameter('admin', $admin)
            ->setParameter('now', new \DateTime());

        if ($event) {
            $qb->andWhere('m.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Get message statistics for an event
     */
    public function getEventMessageStats(Event $event): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('
                COUNT(m.id) as total_messages,
                COUNT(CASE WHEN m.isRead = false THEN 1 END) as unread_messages,
                COUNT(DISTINCT m.sender) as unique_senders
            ')
            ->where('m.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['total_messages'],
            'unread' => (int) $result['unread_messages'],
            'unique_senders' => (int) $result['unique_senders']
        ];
    }
}