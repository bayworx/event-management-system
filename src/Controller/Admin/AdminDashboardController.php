<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Repository\AttendeeRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        EventRepository $eventRepository,
        AttendeeRepository $attendeeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Administrator $admin */
        $admin = $this->getUser();
        
        if (!$admin instanceof Administrator) {
            throw $this->createAccessDeniedException('Admin access required');
        }

        // Update last login time
        $admin->updateLastLogin();
        $entityManager->persist($admin);
        $entityManager->flush();

        // Get admin's managed events
        $managedEvents = $eventRepository->findManagedByAdministrator($admin);
        
        // Get statistics
        $stats = [
            'total_events' => count($managedEvents),
            'upcoming_events' => count(array_filter($managedEvents, fn($e) => $e->getStartDate() >= new \DateTime())),
            'past_events' => count(array_filter($managedEvents, fn($e) => $e->getStartDate() < new \DateTime())),
            'total_attendees' => 0,
        ];

        // Calculate total attendees across managed events
        foreach ($managedEvents as $event) {
            $stats['total_attendees'] += $attendeeRepository->countByEvent($event);
        }

        // Get recent events
        $recentEvents = array_slice(
            array_filter($managedEvents, fn($e) => $e->getStartDate() >= new \DateTime()),
            0,
            5
        );

        return $this->render('admin/dashboard.html.twig', [
            'admin' => $admin,
            'managed_events' => $managedEvents,
            'recent_events' => $recentEvents,
            'stats' => $stats,
        ]);
    }
}