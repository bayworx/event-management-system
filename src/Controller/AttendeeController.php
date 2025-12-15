<?php

namespace App\Controller;

use App\Entity\Attendee;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/attendee')]
class AttendeeController extends AbstractController
{
    #[Route('/dashboard', name: 'attendee_dashboard')]
    public function dashboard(): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        
        if (!$attendee instanceof Attendee) {
            throw $this->createAccessDeniedException('Attendee access required');
        }

        $event = $attendee->getEvent();

        return $this->render('attendee/dashboard.html.twig', [
            'attendee' => $attendee,
            'event' => $event,
        ]);
    }

    #[Route('/messages', name: 'attendee_messages')]
    #[IsGranted('ROLE_ATTENDEE')]
    public function messages(MessageRepository $messageRepository): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        
        if (!$attendee instanceof Attendee) {
            throw $this->createAccessDeniedException('Attendee access required');
        }

        // Get all messages for this attendee
        $messages = $messageRepository->findBy(
            ['sender' => $attendee],
            ['sentAt' => 'DESC']
        );

        return $this->render('attendee/messages.html.twig', [
            'attendee' => $attendee,
            'messages' => $messages,
        ]);
    }

    #[Route('/message/send', name: 'attendee_message_send', methods: ['POST'])]
    #[IsGranted('ROLE_ATTENDEE')]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        
        if (!$attendee instanceof Attendee) {
            throw $this->createAccessDeniedException('Attendee access required');
        }

        $subject = $request->request->get('subject');
        $content = $request->request->get('content');
        $returnUrl = $request->request->get('return_url', $this->generateUrl('attendee_messages'));

        if (empty(trim($subject)) || empty(trim($content))) {
            $this->addFlash('error', 'Subject and message cannot be empty.');
            return $this->redirect($returnUrl);
        }

        // Get event administrators - send to first admin or super admin
        $event = $attendee->getEvent();
        $administrators = $event->getAdministrators();
        
        if ($administrators->isEmpty()) {
            $this->addFlash('error', 'No administrators available to receive your message.');
            return $this->redirect($returnUrl);
        }

        // Send to first administrator
        $recipient = $administrators->first();

        $message = new Message();
        $message->setSubject($subject);
        $message->setContent($content);
        $message->setSender($attendee);
        $message->setRecipient($recipient);
        $message->setEvent($event);

        $entityManager->persist($message);
        $entityManager->flush();

        $this->addFlash('success', 'Your message has been sent to the event administrators.');
        return $this->redirect($returnUrl);
    }

    #[Route('/message/{id}', name: 'attendee_message_view', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ATTENDEE')]
    public function viewMessage(Message $message, MessageRepository $messageRepository): Response
    {
        /** @var Attendee $attendee */
        $attendee = $this->getUser();
        
        if (!$attendee instanceof Attendee) {
            throw $this->createAccessDeniedException('Attendee access required');
        }

        // Check if this message belongs to the attendee
        if ($message->getSender() !== $attendee) {
            throw $this->createAccessDeniedException('You cannot view this message.');
        }

        // Get conversation thread
        $thread = $messageRepository->findConversationThread($message);

        return $this->render('attendee/message_view.html.twig', [
            'attendee' => $attendee,
            'message' => $message,
            'thread' => $thread,
        ]);
    }
}
