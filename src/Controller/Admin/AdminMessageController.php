<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Entity\Event;
use App\Entity\Message;
use App\Repository\EventRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class AdminMessageController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_message_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();
        
        $eventId = $request->query->get('event');
        $status = $request->query->get('status');
        $unreadOnly = $request->query->getBoolean('unread_only');
        
        $event = null;
        if ($eventId) {
            $event = $this->eventRepository->find($eventId);
            if ($event && !$admin->canManageEvent($event)) {
                throw $this->createAccessDeniedException('You cannot manage messages for this event.');
            }
        }

        $query = $this->messageRepository->findForAdministrator($admin, $event, $status, $unreadOnly);
        
        $messages = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        // Get managed events for filter dropdown
        $managedEvents = $admin->isSuperAdmin() 
            ? $this->eventRepository->findAll()
            : $admin->getManagedEvents()->toArray();

        // Get unread count
        $unreadCount = $this->messageRepository->countUnreadForAdmin($admin, $event);

        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages,
            'managed_events' => $managedEvents,
            'current_event' => $event,
            'current_status' => $status,
            'unread_only' => $unreadOnly,
            'unread_count' => $unreadCount,
        ]);
    }

    #[Route('/{id}', name: 'admin_message_show', requirements: ['id' => '\d+'])]
    public function show(Message $message): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        // Check if admin can view this message
        if ($message->getRecipient() !== $admin) {
            throw $this->createAccessDeniedException('You cannot view this message.');
        }

        // Mark message as read if not already
        if (!$message->isRead()) {
            $message->markAsRead();
            $this->entityManager->flush();
        }

        // Get conversation thread
        $thread = $this->messageRepository->findConversationThread($message);

        return $this->render('admin/message/show.html.twig', [
            'message' => $message,
            'thread' => $thread,
        ]);
    }

    #[Route('/{id}/reply', name: 'admin_message_reply', requirements: ['id' => '\d+'])]
    public function reply(Message $originalMessage, Request $request): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        // Check if admin can reply to this message
        if ($originalMessage->getRecipient() !== $admin) {
            throw $this->createAccessDeniedException('You cannot reply to this message.');
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            
            if (empty(trim($content))) {
                $this->addFlash('error', 'Reply content cannot be empty.');
            } else {
                // Create reply message (admin -> attendee)
                $reply = new Message();
                $reply->setSubject('Re: ' . $originalMessage->getSubject());
                $reply->setContent($content);
                $reply->setSender($originalMessage->getSender()); // Keep original sender
                $reply->setRecipient($admin); // Keep admin as recipient for consistency
                $reply->setEvent($originalMessage->getEvent());
                $reply->setReplyTo($originalMessage);

                $this->entityManager->persist($reply);
                
                // Update original message status
                $originalMessage->setStatus('replied');
                
                $this->entityManager->flush();

                $this->addFlash('success', 'Your reply has been sent successfully.');
                return $this->redirectToRoute('admin_message_show', ['id' => $originalMessage->getId()]);
            }
        }

        return $this->render('admin/message/reply.html.twig', [
            'message' => $originalMessage,
        ]);
    }

    #[Route('/{id}/mark-read', name: 'admin_message_mark_read', methods: ['POST'])]
    public function markAsRead(Message $message): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        if ($message->getRecipient() !== $admin) {
            throw $this->createAccessDeniedException();
        }

        $message->markAsRead();
        $this->entityManager->flush();

        $this->addFlash('success', 'Message marked as read.');
        return $this->redirectToRoute('admin_message_index');
    }

    #[Route('/mark-all-read', name: 'admin_message_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(Request $request): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        $eventId = $request->request->get('event_id');
        $event = null;
        
        if ($eventId) {
            $event = $this->eventRepository->find($eventId);
            if ($event && !$admin->canManageEvent($event)) {
                throw $this->createAccessDeniedException();
            }
        }

        $count = $this->messageRepository->markAllAsReadForAdmin($admin, $event);
        
        $this->addFlash('success', sprintf('%d messages marked as read.', $count));
        return $this->redirectToRoute('admin_message_index', $event ? ['event' => $event->getId()] : []);
    }

    #[Route('/{id}/archive', name: 'admin_message_archive', methods: ['POST'])]
    public function archive(Message $message): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        if ($message->getRecipient() !== $admin) {
            throw $this->createAccessDeniedException();
        }

        $message->setStatus('archived');
        $this->entityManager->flush();

        $this->addFlash('success', 'Message archived successfully.');
        return $this->redirectToRoute('admin_message_index');
    }

    #[Route('/bulk-action', name: 'admin_message_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        $action = $request->request->get('action');
        $messageIds = $request->request->all('message_ids');

        if (empty($messageIds)) {
            $this->addFlash('error', 'No messages selected.');
            return $this->redirectToRoute('admin_message_index');
        }

        $messages = $this->messageRepository->findBy([
            'id' => $messageIds,
            'recipient' => $admin
        ]);

        $count = 0;
        foreach ($messages as $message) {
            switch ($action) {
                case 'mark_read':
                    if (!$message->isRead()) {
                        $message->markAsRead();
                        $count++;
                    }
                    break;
                case 'archive':
                    if ($message->getStatus() !== 'archived') {
                        $message->setStatus('archived');
                        $count++;
                    }
                    break;
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            
            $actionLabel = match($action) {
                'mark_read' => 'marked as read',
                'archive' => 'archived',
                default => 'processed'
            };
            
            $this->addFlash('success', sprintf('%d messages %s.', $count, $actionLabel));
        }

        return $this->redirectToRoute('admin_message_index');
    }
}