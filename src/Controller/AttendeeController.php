<?php

namespace App\Controller;

use App\Entity\Attendee;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}