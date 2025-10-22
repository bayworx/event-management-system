<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\EventFile;
use App\Form\AdminEventType;
use App\Form\AdminEventFilterType;
use App\Form\EventFileType;
use App\Repository\EventRepository;
use App\Repository\AdministratorRepository;
use App\Repository\EventPresenterRepository;
use App\Repository\EventFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private EventPresenterRepository $eventPresenterRepository,
        private EventFileRepository $eventFileRepository,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('/', name: 'admin_event_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getUser();
        $filterForm = $this->createForm(AdminEventFilterType::class);
        $filterForm->handleRequest($request);

        // Build query based on user permissions
        if ($currentUser->isSuperAdmin()) {
            $queryBuilder = $this->eventRepository->createQueryBuilder('e')
                ->leftJoin('e.agendaItems', 'ai')
                ->addSelect('ai');
        } else {
            // Regular admins only see their managed events
            $queryBuilder = $this->eventRepository->createQueryBuilder('e')
                ->leftJoin('e.agendaItems', 'ai')
                ->addSelect('ai')
                ->join('e.administrators', 'a')
                ->where('a = :admin')
                ->setParameter('admin', $currentUser);
        }

        $queryBuilder->orderBy('e.startDate', 'DESC');

        // Apply filters
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
            
            if (!empty($filters['search'])) {
                $queryBuilder
                    ->andWhere('e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search')
                    ->setParameter('search', '%' . $filters['search'] . '%');
            }

            if (isset($filters['status'])) {
                if ($filters['status'] === 'active') {
                    $queryBuilder->andWhere('e.isActive = true');
                } elseif ($filters['status'] === 'inactive') {
                    $queryBuilder->andWhere('e.isActive = false');
                }
            }

            if (isset($filters['dateRange'])) {
                $now = new \DateTime();
                if ($filters['dateRange'] === 'upcoming') {
                    $queryBuilder->andWhere('e.startDate >= :now')
                        ->setParameter('now', $now);
                } elseif ($filters['dateRange'] === 'past') {
                    $queryBuilder->andWhere('e.startDate < :now')
                        ->setParameter('now', $now);
                } elseif ($filters['dateRange'] === 'ongoing') {
                    $queryBuilder
                        ->andWhere('e.startDate <= :now AND (e.endDate IS NULL OR e.endDate >= :now)')
                        ->setParameter('now', $now);
                }
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/event/index.html.twig', [
            'pagination' => $pagination,
            'filter_form' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AdministratorRepository $adminRepository): Response
    {
        // Start output buffering to handle deprecation warnings
        if ($request->isMethod('POST')) {
            ob_start();
        }
        
        $event = new Event();
        $currentUser = $this->getUser();
        
        // Auto-assign current admin to the event
        $event->addAdministrator($currentUser);
        
        $form = $this->createForm(AdminEventType::class, $event, [
            'current_user' => $currentUser
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Generate slug from title
                $slug = $this->slugger->slug($event->getTitle())->lower();
                $originalSlug = $slug;
                $counter = 1;
                
                // Ensure slug is unique
                while ($this->eventRepository->findOneBySlug($slug)) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                
                $event->setSlug($slug);
                $event->setCreatedAt(new \DateTime());
                
                // Handle event presenters
                foreach ($event->getEventPresenters() as $eventPresenter) {
                    $eventPresenter->setEvent($event);
                }
                $event->autoAssignPresenterSortOrders();

                $this->entityManager->persist($event);
                $this->entityManager->flush();

                // Clean any buffered output before redirect
                if (ob_get_level()) {
                    ob_end_clean();
                }

                $this->addFlash('success', 'Event created successfully.');
                return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
            } catch (\Exception $e) {
                // Clean any buffered output on error
                if (ob_get_level()) {
                    ob_end_clean();
                }
                $this->addFlash('error', 'Error creating event: ' . $e->getMessage());
            }
        }
        
        // Clean buffer if still active for GET requests or failed POST
        if (ob_get_level()) {
            ob_end_clean();
        }

        return $this->render('admin/event/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $event = $this->eventRepository->createQueryBuilder('e')
            ->leftJoin('e.agendaItems', 'ai')
            ->addSelect('ai')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
            
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        return $this->render('admin/event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);
        
        $currentUser = $this->getUser();
        $form = $this->createForm(AdminEventType::class, $event, [
            'current_user' => $currentUser
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update slug if title changed
            $newSlug = $this->slugger->slug($event->getTitle())->lower();
            if ($newSlug != $event->getSlug()) {
                $originalSlug = $newSlug;
                $counter = 1;
                
                // Ensure slug is unique
                while ($this->eventRepository->findOneBySlug($newSlug) && $newSlug != $event->getSlug()) {
                    $newSlug = $originalSlug . '-' . $counter++;
                }
                
                $event->setSlug($newSlug);
            }
            
            // Handle event presenters
            foreach ($event->getEventPresenters() as $eventPresenter) {
                $eventPresenter->setEvent($event);
            }
            $event->autoAssignPresenterSortOrders();
            
            $event->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $this->addFlash('success', 'Event updated successfully.');
            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_event_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        if ($this->isCsrfTokenValid('toggle-status-' . $event->getId(), $request->request->get('_token'))) {
            $event->setIsActive(!$event->isActive());
            $event->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $status = $event->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "Event {$status} successfully.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/delete', name: 'admin_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        if ($this->isCsrfTokenValid('delete-' . $event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Event deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/clone', name: 'admin_event_clone', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function clone(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        if ($this->isCsrfTokenValid('clone-' . $event->getId(), $request->request->get('_token'))) {
            $clonedEvent = new Event();
            $clonedEvent->setTitle($event->getTitle() . ' (Copy)');
            $clonedEvent->setDescription($event->getDescription());
            $clonedEvent->setLocation($event->getLocation());
            $clonedEvent->setMaxAttendees($event->getMaxAttendees());
            $clonedEvent->setIsActive(false); // Start as inactive
            
            // Set dates to future (optional, you might want to set these to null)
            $startDate = new \DateTime();
            $startDate->modify('+1 month');
            $clonedEvent->setStartDate($startDate);
            
            if ($event->getEndDate()) {
                $duration = $event->getStartDate()->diff($event->getEndDate());
                $endDate = clone $startDate;
                $endDate->add($duration);
                $clonedEvent->setEndDate($endDate);
            }
            
            // Copy administrators
            foreach ($event->getAdministrators() as $admin) {
                $clonedEvent->addAdministrator($admin);
            }
            
            // Generate unique slug
            $slug = $this->slugger->slug($clonedEvent->getTitle())->lower();
            $originalSlug = $slug;
            $counter = 1;
            
            while ($this->eventRepository->findOneBySlug($slug)) {
                $slug = $originalSlug . '-' . $counter++;
            }
            
            $clonedEvent->setSlug($slug);
            $clonedEvent->setCreatedAt(new \DateTime());

            $this->entityManager->persist($clonedEvent);
            $this->entityManager->flush();

            $this->addFlash('success', 'Event cloned successfully. Please review and update the details.');
            return $this->redirectToRoute('admin_event_edit', ['id' => $clonedEvent->getId()]);
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/badges/presenters', name: 'admin_event_presenter_badges', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function presenterBadges(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        return $this->render('admin/event/badges/presenters.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/badges/attendees', name: 'admin_event_attendee_badges', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function attendeeBadges(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        return $this->render('admin/event/badges/attendees.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/badges/attendees/zebra', name: 'admin_event_attendee_badges_zebra', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function attendeeBadgesZebra(int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        $response = $this->render('admin/event/badges/attendees-zpl.html.twig', [
            'event' => $event,
        ]);
        
        // Set appropriate headers for ZPL format download
        if ($request->query->get('format') === 'zpl') {
            $response->headers->set('Content-Type', 'text/plain');
            $response->headers->set('Content-Disposition', 'attachment; filename="event-badges-' . $event->getId() . '.zpl"');
        }
        
        return $response;
    }

    #[Route('/{id}/files/new', name: 'admin_event_file_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newFile(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        $eventFile = new EventFile();
        $eventFile->setEvent($event);
        
        $form = $this->createForm(EventFileType::class, $eventFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($eventFile);
                $this->entityManager->flush();

                $this->addFlash('success', 'File uploaded successfully.');
                return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error uploading file: ' . $e->getMessage());
            }
        }

        return $this->render('admin/event/files/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{eventId}/files/{fileId}/edit', name: 'admin_event_file_edit', methods: ['GET', 'POST'], requirements: ['eventId' => '\d+', 'fileId' => '\d+'])]
    public function editFile(Request $request, int $eventId, int $fileId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        $eventFile = $this->eventFileRepository->find($fileId);
        if (!$eventFile || $eventFile->getEvent() !== $event) {
            throw $this->createNotFoundException('File not found');
        }
        
        $form = $this->createForm(EventFileType::class, $eventFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();

                $this->addFlash('success', 'File updated successfully.');
                return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating file: ' . $e->getMessage());
            }
        }

        return $this->render('admin/event/files/edit.html.twig', [
            'event' => $event,
            'file' => $eventFile,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{eventId}/files/{fileId}/delete', name: 'admin_event_file_delete', methods: ['POST'], requirements: ['eventId' => '\d+', 'fileId' => '\d+'])]
    public function deleteFile(Request $request, int $eventId, int $fileId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        $eventFile = $this->eventFileRepository->find($fileId);
        if (!$eventFile || $eventFile->getEvent() !== $event) {
            throw $this->createNotFoundException('File not found');
        }

        if ($this->isCsrfTokenValid('delete-event-file-' . $eventFile->getId(), $request->request->get('_token'))) {
            try {
                $this->entityManager->remove($eventFile);
                $this->entityManager->flush();

                $this->addFlash('success', 'File deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting file: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{eventId}/files/{fileId}/toggle', name: 'admin_event_file_toggle', methods: ['POST'], requirements: ['eventId' => '\d+', 'fileId' => '\d+'])]
    public function toggleFileStatus(Request $request, int $eventId, int $fileId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $this->checkEventAccess($event);

        $eventFile = $this->eventFileRepository->find($fileId);
        if (!$eventFile || $eventFile->getEvent() !== $event) {
            throw $this->createNotFoundException('File not found');
        }

        if ($this->isCsrfTokenValid('toggle-event-file-' . $eventFile->getId(), $request->request->get('_token'))) {
            try {
                $eventFile->setIsActive(!$eventFile->isActive());
                $this->entityManager->flush();

                $status = $eventFile->isActive() ? 'activated' : 'deactivated';
                $this->addFlash('success', "File {$status} successfully.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating file status: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
    }

    private function checkEventAccess(Event $event): void
    {
        $currentUser = $this->getUser();
        
        // Super admins can access all events
        if ($currentUser->isSuperAdmin()) {
            return;
        }
        
        // Regular admins can only access their managed events
        if (!$currentUser->canManageEvent($event)) {
            throw $this->createAccessDeniedException('You do not have permission to manage this event.');
        }
    }
}
