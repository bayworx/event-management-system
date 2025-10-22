<?php

namespace App\Controller;

use App\Entity\Attendee;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/attendee/messages')]
#[IsGranted('ROLE_ATTENDEE')]
class AttendeeMessageController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'attendee_message_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        
        $query = $this->messageRepository->findForAttendee($attendee, $attendee->getEvent());
        
        $messages = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('attendee/message/index.html.twig', [
            'messages' => $messages,
            'event' => $attendee->getEvent(),
        ]);
    }

    #[Route('/{id}', name: 'attendee_message_show', requirements: ['id' => '\d+'])]
    public function show(Message $message): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();

        // Check if attendee can view this message
        if ($message->getSender() !== $attendee) {
            throw $this->createAccessDeniedException('You cannot view this message.');
        }

        // Get conversation thread
        $thread = $this->messageRepository->findConversationThread($message);

        return $this->render('attendee/message/show.html.twig', [
            'message' => $message,
            'thread' => $thread,
        ]);
    }

    #[Route('/new', name: 'attendee_message_new')]
    public function new(Request $request): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        $event = $attendee->getEvent();

        if (!$event) {
            throw $this->createNotFoundException('You must be registered for an event to send messages.');
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message, [
            'event' => $event,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($attendee);
            $message->setEvent($event);
            
            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your message has been sent to the event administrators.');
            return $this->redirectToRoute('attendee_message_show', ['id' => $message->getId()]);
        }

        return $this->render('attendee/message/new.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/contact-admin', name: 'attendee_message_contact_admin')]
    public function contactAdmin(Request $request): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        $event = $attendee->getEvent();

        if (!$event) {
            throw $this->createNotFoundException('You must be registered for an event to send messages.');
        }

        // Get event administrators
        $administrators = $event->getAdministrators();
        
        if ($administrators->isEmpty()) {
            $this->addFlash('error', 'No administrators are available for this event.');
            return $this->redirectToRoute('attendee_event_show', ['slug' => $event->getSlug()]);
        }

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject');
            $content = $request->request->get('content');
            $priority = $request->request->get('priority', 'normal');

            if (empty(trim($subject)) || empty(trim($content))) {
                $this->addFlash('error', 'Subject and message content are required.');
            } else {
                // Send message to all event administrators
                $messagesSent = 0;
                foreach ($administrators as $admin) {
                    $message = new Message();
                    $message->setSubject($subject);
                    $message->setContent($content);
                    $message->setPriority($priority);
                    $message->setSender($attendee);
                    $message->setRecipient($admin);
                    $message->setEvent($event);
                    
                    $this->entityManager->persist($message);
                    $messagesSent++;
                }
                
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('Your message has been sent to %d administrator(s).', $messagesSent));
                return $this->redirectToRoute('attendee_message_index');
            }
        }

        return $this->render('attendee/message/contact_admin.html.twig', [
            'event' => $event,
            'administrators' => $administrators,
        ]);
    }

    #[Route('/quick-message', name: 'attendee_message_quick', methods: ['POST'])]
    public function quickMessage(Request $request): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        $event = $attendee->getEvent();

        if (!$event) {
            return $this->json(['success' => false, 'message' => 'You must be registered for an event.']);
        }

        $content = $request->request->get('message');
        $subject = $request->request->get('subject', 'Quick message from attendee');

        if (empty(trim($content))) {
            return $this->json(['success' => false, 'message' => 'Message content is required.']);
        }

        $administrators = $event->getAdministrators();
        if ($administrators->isEmpty()) {
            return $this->json(['success' => false, 'message' => 'No administrators available.']);
        }

        $messagesSent = 0;
        foreach ($administrators as $admin) {
            $message = new Message();
            $message->setSubject($subject);
            $message->setContent($content);
            $message->setSender($attendee);
            $message->setRecipient($admin);
            $message->setEvent($event);
            
            $this->entityManager->persist($message);
            $messagesSent++;
        }
        
        $this->entityManager->flush();

        return $this->json([
            'success' => true, 
            'message' => sprintf('Message sent to %d administrator(s).', $messagesSent)
        ]);
    }
}