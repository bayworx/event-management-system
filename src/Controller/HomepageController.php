<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EventRepository $eventRepository): Response
    {
        $upcomingEvents = $eventRepository->findUpcoming(6);
        $stats = $eventRepository->getStatistics();

        return $this->render('homepage/index.html.twig', [
            'upcoming_events' => $upcomingEvents,
            'stats' => $stats,
        ]);
    }
}