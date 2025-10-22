<?php

namespace App\Controller;

use App\Service\FeaturedEventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/featured-events')]
class FeaturedEventApiController extends AbstractController
{
    public function __construct(
        private FeaturedEventService $featuredEventService
    ) {}

    #[Route('/{id}/view', name: 'api_featured_event_view', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function recordView(int $id): JsonResponse
    {
        try {
            $this->featuredEventService->recordView($id);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'View recorded'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to record view'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/click', name: 'api_featured_event_click', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function recordClick(int $id, Request $request): JsonResponse
    {
        try {
            $this->featuredEventService->recordClick($id);
            
            // Get the featured event to return redirect URL
            $featuredEvent = $this->featuredEventService->getFeaturedEvent($id);
            
            if (!$featuredEvent) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Featured event not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $redirectUrl = $featuredEvent->getEffectiveLinkUrl();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Click recorded',
                'redirect_url' => $redirectUrl
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to record click'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/rotation', name: 'api_featured_events_rotation')]
    public function getRotationData(Request $request): JsonResponse
    {
        try {
            $limit = min($request->query->getInt('limit', 5), 10); // Max 10 items
            
            $featuredEvents = $this->featuredEventService->getFeaturedEventsForRotation($limit);
            $settings = $this->featuredEventService->getRotationSettings($featuredEvents);
            
            $data = [];
            foreach ($featuredEvents as $featuredEvent) {
                $data[] = [
                    'id' => $featuredEvent->getId(),
                    'title' => $featuredEvent->getTitle(),
                    'description' => $featuredEvent->getDescription(),
                    'imageUrl' => $featuredEvent->getEffectiveBannerImage(),
                    'linkUrl' => $featuredEvent->getEffectiveLinkUrl(),
                    'linkText' => $featuredEvent->getEffectiveLinkText(),
                    'priority' => $featuredEvent->getPriority(),
                    'relatedEvent' => $featuredEvent->getRelatedEvent() ? [
                        'id' => $featuredEvent->getRelatedEvent()->getId(),
                        'title' => $featuredEvent->getRelatedEvent()->getTitle(),
                        'slug' => $featuredEvent->getRelatedEvent()->getSlug(),
                    ] : null
                ];
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $data,
                'settings' => $settings,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to fetch featured events',
                'data' => [],
                'settings' => [],
                'count' => 0
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}