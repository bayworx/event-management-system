<?php

namespace App\Controller\Admin;

use App\Entity\Attendee;
use App\Entity\Event;
use App\Repository\AttendeeRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/attendees')]
#[IsGranted('ROLE_ADMIN')]
class AdminAttendeeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AttendeeRepository $attendeeRepository,
        private EventRepository $eventRepository
    ) {
    }

    #[Route('/', name: 'admin_attendee_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getUser();
        $search = $request->query->get('search');
        $eventFilter = $request->query->get('event');
        $statusFilter = $request->query->get('status');

        // Build query based on user permissions
        $queryBuilder = $this->attendeeRepository->createQueryBuilder('a')
            ->leftJoin('a.event', 'e')
            ->addSelect('e');

        // Apply permission filters
        if (!$currentUser->isSuperAdmin()) {
            // Regular admins only see attendees from their managed events
            $queryBuilder
                ->join('e.administrators', 'admin')
                ->andWhere('admin = :admin')
                ->setParameter('admin', $currentUser);
        }

        // Apply search filter
        if ($search) {
            $queryBuilder
                ->andWhere('a.name LIKE :search OR a.email LIKE :search OR a.organization LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Apply event filter
        if ($eventFilter) {
            $queryBuilder
                ->andWhere('e.id = :eventId')
                ->setParameter('eventId', $eventFilter);
        }

        // Apply status filter
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'verified':
                    $queryBuilder->andWhere('a.isVerified = true');
                    break;
                case 'unverified':
                    $queryBuilder->andWhere('a.isVerified = false');
                    break;
                case 'checked_in':
                    $queryBuilder->andWhere('a.isCheckedIn = true');
                    break;
            }
        }

        $queryBuilder->orderBy('a.registeredAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Get available events for filter dropdown
        $eventsQueryBuilder = $this->eventRepository->createQueryBuilder('e');
        if (!$currentUser->isSuperAdmin()) {
            $eventsQueryBuilder
                ->join('e.administrators', 'admin')
                ->where('admin = :admin')
                ->setParameter('admin', $currentUser);
        }
        $availableEvents = $eventsQueryBuilder
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/attendee/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'event_filter' => $eventFilter,
            'status_filter' => $statusFilter,
            'available_events' => $availableEvents,
        ]);
    }

    #[Route('/{id}', name: 'admin_attendee_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $attendee = $this->attendeeRepository->find($id);
        if (!$attendee) {
            throw $this->createNotFoundException('Attendee not found');
        }

        $this->checkAttendeeAccess($attendee);

        return $this->render('admin/attendee/show.html.twig', [
            'attendee' => $attendee,
        ]);
    }

    #[Route('/{id}/toggle-verification', name: 'admin_attendee_toggle_verification', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleVerification(Request $request, int $id): Response
    {
        $attendee = $this->attendeeRepository->find($id);
        if (!$attendee) {
            throw $this->createNotFoundException('Attendee not found');
        }

        $this->checkAttendeeAccess($attendee);

        if ($this->isCsrfTokenValid('toggle-verification-' . $attendee->getId(), $request->request->get('_token'))) {
            $attendee->setIsVerified(!$attendee->isVerified());
            $this->entityManager->flush();

            $status = $attendee->isVerified() ? 'verified' : 'unverified';
            $this->addFlash('success', "Attendee marked as {$status} successfully.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_attendee_show', ['id' => $attendee->getId()]);
    }

    #[Route('/{id}/toggle-checkin', name: 'admin_attendee_toggle_checkin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCheckin(Request $request, int $id): Response
    {
        $attendee = $this->attendeeRepository->find($id);
        if (!$attendee) {
            throw $this->createNotFoundException('Attendee not found');
        }

        $this->checkAttendeeAccess($attendee);

        if ($this->isCsrfTokenValid('toggle-checkin-' . $attendee->getId(), $request->request->get('_token'))) {
            $attendee->setIsCheckedIn(!$attendee->isCheckedIn());
            $this->entityManager->flush();

            $status = $attendee->isCheckedIn() ? 'checked in' : 'checked out';
            $this->addFlash('success', "Attendee {$status} successfully.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_attendee_show', ['id' => $attendee->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_attendee_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $attendee = $this->attendeeRepository->find($id);
        if (!$attendee) {
            throw $this->createNotFoundException('Attendee not found');
        }

        $this->checkAttendeeAccess($attendee);

        if ($this->isCsrfTokenValid('delete-' . $attendee->getId(), $request->request->get('_token'))) {
            $eventTitle = $attendee->getEvent()->getTitle();
            $this->entityManager->remove($attendee);
            $this->entityManager->flush();

            $this->addFlash('success', "Attendee removed from {$eventTitle} successfully.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_attendee_index');
    }

    #[Route('/export', name: 'admin_attendee_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $currentUser = $this->getUser();
        $eventFilter = $request->query->get('event');
        
        // Build query based on user permissions
        $queryBuilder = $this->attendeeRepository->createQueryBuilder('a')
            ->leftJoin('a.event', 'e')
            ->addSelect('e');

        if (!$currentUser->isSuperAdmin()) {
            $queryBuilder
                ->join('e.administrators', 'admin')
                ->andWhere('admin = :admin')
                ->setParameter('admin', $currentUser);
        }

        if ($eventFilter) {
            $queryBuilder
                ->andWhere('e.id = :eventId')
                ->setParameter('eventId', $eventFilter);
        }

        $attendees = $queryBuilder
            ->orderBy('e.title', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Generate CSV
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="attendees-export.csv"');

        $handle = fopen('php://temp', 'r+');
        
        // CSV Headers
        fputcsv($handle, [
            'Event',
            'Name',
            'Email',
            'Phone',
            'Organization',
            'Job Title',
            'Verified',
            'Checked In',
            'Registered At',
            'Verified At',
            'Checked In At'
        ]);

        // CSV Data
        foreach ($attendees as $attendee) {
            fputcsv($handle, [
                $attendee->getEvent()->getTitle(),
                $attendee->getName(),
                $attendee->getEmail(),
                $attendee->getPhone() ?: '',
                $attendee->getOrganization() ?: '',
                $attendee->getJobTitle() ?: '',
                $attendee->isVerified() ? 'Yes' : 'No',
                $attendee->isCheckedIn() ? 'Yes' : 'No',
                $attendee->getRegisteredAt()->format('Y-m-d H:i:s'),
                $attendee->getEmailVerifiedAt() ? $attendee->getEmailVerifiedAt()->format('Y-m-d H:i:s') : '',
                $attendee->getCheckedInAt() ? $attendee->getCheckedInAt()->format('Y-m-d H:i:s') : ''
            ]);
        }

        rewind($handle);
        $response->setContent(stream_get_contents($handle));
        fclose($handle);

        return $response;
    }

    private function checkAttendeeAccess(Attendee $attendee): void
    {
        $currentUser = $this->getUser();

        // Super admins can access all attendees
        if ($currentUser->isSuperAdmin()) {
            return;
        }

        // Regular admins can only access attendees from their managed events
        if (!$currentUser->canManageEvent($attendee->getEvent())) {
            throw $this->createAccessDeniedException('You do not have permission to manage this attendee.');
        }
    }
}