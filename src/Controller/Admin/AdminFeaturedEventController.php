<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Entity\FeaturedEvent;
use App\Form\FeaturedEventType;
use App\Service\FeaturedEventService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/featured-events')]
#[IsGranted('ROLE_ADMIN')]
class AdminFeaturedEventController extends AbstractController
{
    public function __construct(
        private FeaturedEventService $featuredEventService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_featured_events_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        $queryBuilder = $this->entityManager->getRepository(FeaturedEvent::class)
            ->createQueryBuilder('fe')
            ->leftJoin('fe.relatedEvent', 'e')
            ->leftJoin('fe.createdBy', 'cb')
            ->addSelect('e', 'cb')
            ->orderBy('fe.priority', 'DESC')
            ->addOrderBy('fe.createdAt', 'DESC');

        // Filter by active status if requested
        if ($request->query->get('status') === 'active') {
            $queryBuilder->andWhere('fe.isActive = :active')
                        ->setParameter('active', true);
        } elseif ($request->query->get('status') === 'inactive') {
            $queryBuilder->andWhere('fe.isActive = :active')
                        ->setParameter('active', false);
        }

        // Filter by display type if requested
        if ($displayType = $request->query->get('display_type')) {
            $queryBuilder->andWhere('fe.displayType = :displayType')
                        ->setParameter('displayType', $displayType);
        }

        $featuredEvents = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        // Get statistics for dashboard
        $statistics = $this->featuredEventService->getStatistics();
        $expiringSoon = $this->featuredEventService->getExpiringSoon();

        return $this->render('admin/featured_events/index.html.twig', [
            'featured_events' => $featuredEvents,
            'statistics' => $statistics,
            'expiring_soon' => $expiringSoon,
            'current_filters' => [
                'status' => $request->query->get('status'),
                'display_type' => $request->query->get('display_type')
            ]
        ]);
    }

    #[Route('/new', name: 'admin_featured_events_new')]
    public function new(Request $request): Response
    {
        /** @var Administrator $admin */
        $admin = $this->getUser();

        $featuredEvent = new FeaturedEvent();
        $featuredEvent->setCreatedBy($admin);

        $form = $this->createForm(FeaturedEventType::class, $featuredEvent);
        
        // Set initial displaySettings JSON value for the form
        if ($featuredEvent->getDisplaySettings()) {
            $form->get('displaySettings')->setData(json_encode($featuredEvent->getDisplaySettings(), JSON_PRETTY_PRINT));
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle displaySettings JSON manually since it's unmapped
                $displaySettingsJson = $form->get('displaySettings')->getData();
                if ($displaySettingsJson) {
                    $displaySettings = json_decode($displaySettingsJson, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $featuredEvent->setDisplaySettings($displaySettings);
                    } else {
                        $this->addFlash('error', 'Invalid JSON in display settings: ' . json_last_error_msg());
                        return $this->render('admin/featured_events/new.html.twig', [
                            'form' => $form->createView(),
                            'featured_event' => $featuredEvent,
                        ]);
                    }
                }
                
                $this->featuredEventService->saveFeaturedEvent($featuredEvent);
                $this->addFlash('success', 'Featured event created successfully!');
                
                return $this->redirectToRoute('admin_featured_events_show', [
                    'id' => $featuredEvent->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create featured event: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // Debug: form is submitted but not valid
            $errors = [];
            foreach ($form->getErrors(true, false) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Form validation failed: ' . implode('; ', $errors));
        }

        return $this->render('admin/featured_events/new.html.twig', [
            'form' => $form->createView(),
            'featured_event' => $featuredEvent,
        ]);
    }

    #[Route('/{id}', name: 'admin_featured_events_show', requirements: ['id' => '\d+'])]
    public function show(FeaturedEvent $featuredEvent): Response
    {
        return $this->render('admin/featured_events/show.html.twig', [
            'featured_event' => $featuredEvent,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_featured_events_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, FeaturedEvent $featuredEvent): Response
    {
        $form = $this->createForm(FeaturedEventType::class, $featuredEvent);
        
        // Set initial displaySettings JSON value for the form
        if ($featuredEvent->getDisplaySettings()) {
            $form->get('displaySettings')->setData(json_encode($featuredEvent->getDisplaySettings(), JSON_PRETTY_PRINT));
        }
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle displaySettings JSON manually since it's unmapped
                $displaySettingsJson = $form->get('displaySettings')->getData();
                if ($displaySettingsJson) {
                    $displaySettings = json_decode($displaySettingsJson, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $featuredEvent->setDisplaySettings($displaySettings);
                    } else {
                        $this->addFlash('error', 'Invalid JSON in display settings: ' . json_last_error_msg());
                        return $this->render('admin/featured_events/edit.html.twig', [
                            'form' => $form->createView(),
                            'featured_event' => $featuredEvent,
                        ]);
                    }
                }
                
                $this->featuredEventService->saveFeaturedEvent($featuredEvent);
                $this->addFlash('success', 'Featured event updated successfully!');
                
                return $this->redirectToRoute('admin_featured_events_show', [
                    'id' => $featuredEvent->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update featured event: ' . $e->getMessage());
            }
        }

        return $this->render('admin/featured_events/edit.html.twig', [
            'form' => $form->createView(),
            'featured_event' => $featuredEvent,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_featured_events_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, FeaturedEvent $featuredEvent): Response
    {
        if ($this->isCsrfTokenValid('delete-featured-event-' . $featuredEvent->getId(), $request->request->get('_token'))) {
            try {
                $this->featuredEventService->deleteFeaturedEvent($featuredEvent);
                $this->addFlash('success', 'Featured event deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete featured event: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_featured_events_index');
    }

    #[Route('/{id}/toggle', name: 'admin_featured_events_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, FeaturedEvent $featuredEvent): Response
    {
        if ($this->isCsrfTokenValid('toggle-featured-event-' . $featuredEvent->getId(), $request->request->get('_token'))) {
            try {
                $featuredEvent->setIsActive(!$featuredEvent->isActive());
                $this->featuredEventService->saveFeaturedEvent($featuredEvent);
                
                $status = $featuredEvent->isActive() ? 'activated' : 'deactivated';
                $this->addFlash('success', "Featured event {$status} successfully!");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to toggle featured event: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_featured_events_index');
    }

    #[Route('/analytics', name: 'admin_featured_events_analytics')]
    public function analytics(): Response
    {
        $statistics = $this->featuredEventService->getStatistics();
        $topPerforming = $this->featuredEventService->getTopPerforming(10);
        $expiringSoon = $this->featuredEventService->getExpiringSoon();

        return $this->render('admin/featured_events/analytics.html.twig', [
            'statistics' => $statistics,
            'top_performing' => $topPerforming,
            'expiring_soon' => $expiringSoon,
        ]);
    }

    #[Route('/cleanup', name: 'admin_featured_events_cleanup', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function cleanup(): Response
    {
        try {
            $deactivatedCount = $this->featuredEventService->cleanupExpired();
            
            if ($deactivatedCount > 0) {
                $this->addFlash('success', "Deactivated {$deactivatedCount} expired featured event(s).");
            } else {
                $this->addFlash('info', 'No expired featured events found.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to cleanup expired events: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_featured_events_index');
    }

    #[Route('/clear-cache', name: 'admin_featured_events_clear_cache', methods: ['POST'])]
    public function clearCache(): Response
    {
        try {
            $this->featuredEventService->clearCache();
            $this->addFlash('success', 'Featured events cache cleared successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to clear cache: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_featured_events_index');
    }
}