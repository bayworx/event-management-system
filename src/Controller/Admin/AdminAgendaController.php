<?php

namespace App\Controller\Admin;

use App\Entity\AgendaItem;
use App\Entity\Event;
use App\Form\AgendaItemType;
use App\Repository\AgendaItemRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/agenda')]
#[IsGranted('ROLE_ADMIN')]
class AdminAgendaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AgendaItemRepository $agendaItemRepository,
        private EventRepository $eventRepository
    ) {
    }

    #[Route('/event/{eventId}', name: 'admin_agenda_index', methods: ['GET'], requirements: ['eventId' => '\d+'])]
    public function index(int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $this->checkEventAccess($event);

        $agendaItems = $this->agendaItemRepository->findByEvent($event);

        return $this->render('admin/agenda/index.html.twig', [
            'event' => $event,
            'agenda_items' => $agendaItems,
        ]);
    }

    #[Route('/event/{eventId}/new', name: 'admin_agenda_new', methods: ['GET', 'POST'], requirements: ['eventId' => '\d+'])]
    public function new(Request $request, int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $this->checkEventAccess($event);

        $agendaItem = new AgendaItem();
        $agendaItem->setEvent($event);
        $agendaItem->setIsVisible(true);
        
        // Set default times based on event
        $agendaItem->setStartTime($event->getStartDate());
        
        // Auto-assign sort order
        $nextSortOrder = $this->agendaItemRepository->getNextSortOrder($event);
        $agendaItem->setSortOrder($nextSortOrder);

        $form = $this->createForm(AgendaItemType::class, $agendaItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($agendaItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Agenda item created successfully.');
            return $this->redirectToRoute('admin_agenda_index', ['eventId' => $event->getId()]);
        }

        return $this->render('admin/agenda/new.html.twig', [
            'event' => $event,
            'agenda_item' => $agendaItem,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_agenda_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $agendaItem = $this->agendaItemRepository->find($id);
        if (!$agendaItem) {
            throw $this->createNotFoundException('Agenda item not found');
        }

        $this->checkEventAccess($agendaItem->getEvent());

        return $this->render('admin/agenda/show.html.twig', [
            'agenda_item' => $agendaItem,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_agenda_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $agendaItem = $this->agendaItemRepository->find($id);
        if (!$agendaItem) {
            throw $this->createNotFoundException('Agenda item not found');
        }

        $this->checkEventAccess($agendaItem->getEvent());

        $form = $this->createForm(AgendaItemType::class, $agendaItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $agendaItem->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $this->addFlash('success', 'Agenda item updated successfully.');
            return $this->redirectToRoute('admin_agenda_index', ['eventId' => $agendaItem->getEvent()->getId()]);
        }

        return $this->render('admin/agenda/edit.html.twig', [
            'agenda_item' => $agendaItem,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_agenda_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $agendaItem = $this->agendaItemRepository->find($id);
        if (!$agendaItem) {
            throw $this->createNotFoundException('Agenda item not found');
        }

        $event = $agendaItem->getEvent();
        $this->checkEventAccess($event);

        if ($this->isCsrfTokenValid('delete-agenda-' . $agendaItem->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($agendaItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Agenda item deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_agenda_index', ['eventId' => $event->getId()]);
    }

    #[Route('/{id}/toggle-visibility', name: 'admin_agenda_toggle_visibility', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleVisibility(Request $request, int $id): Response
    {
        $agendaItem = $this->agendaItemRepository->find($id);
        if (!$agendaItem) {
            throw $this->createNotFoundException('Agenda item not found');
        }

        $this->checkEventAccess($agendaItem->getEvent());

        if ($this->isCsrfTokenValid('toggle-visibility-' . $agendaItem->getId(), $request->request->get('_token'))) {
            $agendaItem->setIsVisible(!$agendaItem->isVisible());
            $agendaItem->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $status = $agendaItem->isVisible() ? 'visible' : 'hidden';
            $this->addFlash('success', "Agenda item is now {$status}.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_agenda_index', ['eventId' => $agendaItem->getEvent()->getId()]);
    }

    #[Route('/event/{eventId}/reorder', name: 'admin_agenda_reorder', methods: ['POST'], requirements: ['eventId' => '\d+'])]
    public function reorder(Request $request, int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $this->checkEventAccess($event);

        $items = $request->request->all('items');
        
        if (is_array($items)) {
            foreach ($items as $position => $itemId) {
                $agendaItem = $this->agendaItemRepository->find($itemId);
                if ($agendaItem && $agendaItem->getEvent() === $event) {
                    $agendaItem->setSortOrder($position);
                    $agendaItem->setUpdatedAt(new \DateTime());
                }
            }
            
            $this->entityManager->flush();
            $this->addFlash('success', 'Agenda items reordered successfully.');
        }

        return $this->redirectToRoute('admin_agenda_index', ['eventId' => $eventId]);
    }

    #[Route('/{id}/duplicate', name: 'admin_agenda_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Request $request, int $id): Response
    {
        $originalItem = $this->agendaItemRepository->find($id);
        if (!$originalItem) {
            throw $this->createNotFoundException('Agenda item not found');
        }

        $this->checkEventAccess($originalItem->getEvent());

        if ($this->isCsrfTokenValid('duplicate-agenda-' . $originalItem->getId(), $request->request->get('_token'))) {
            $duplicatedItem = new AgendaItem();
            $duplicatedItem->setTitle($originalItem->getTitle() . ' (Copy)');
            $duplicatedItem->setDescription($originalItem->getDescription());
            $duplicatedItem->setItemType($originalItem->getItemType());
            $duplicatedItem->setSpeaker($originalItem->getSpeaker());
            $duplicatedItem->setLocation($originalItem->getLocation());
            $duplicatedItem->setPresenter($originalItem->getPresenter());
            $duplicatedItem->setEvent($originalItem->getEvent());
            $duplicatedItem->setIsVisible(true);
            
            // Set new times (30 minutes after original)
            if ($originalItem->getStartTime()) {
                $newStartTime = clone $originalItem->getStartTime();
                $newStartTime->modify('+30 minutes');
                $duplicatedItem->setStartTime($newStartTime);
            }
            
            if ($originalItem->getEndTime()) {
                $newEndTime = clone $originalItem->getEndTime();
                $newEndTime->modify('+30 minutes');
                $duplicatedItem->setEndTime($newEndTime);
            }

            // Auto-assign new sort order
            $nextSortOrder = $this->agendaItemRepository->getNextSortOrder($originalItem->getEvent());
            $duplicatedItem->setSortOrder($nextSortOrder);

            $this->entityManager->persist($duplicatedItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Agenda item duplicated successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_agenda_index', ['eventId' => $originalItem->getEvent()->getId()]);
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
            throw $this->createAccessDeniedException('You do not have permission to manage this event\'s agenda.');
        }
    }
}