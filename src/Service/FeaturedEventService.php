<?php

namespace App\Service;

use App\Entity\FeaturedEvent;
use App\Repository\FeaturedEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class FeaturedEventService
{
    private const CACHE_KEY_ROTATION = 'featured_events.rotation';
    private const CACHE_KEY_ACTIVE = 'featured_events.active';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private FeaturedEventRepository $repository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Get featured events for rotation with caching
     */
    public function getFeaturedEventsForRotation(int $limit = 5): array
    {
        return $this->cache->get(
            self::CACHE_KEY_ROTATION . "_limit_{$limit}",
            function (ItemInterface $item) use ($limit) {
                $item->expiresAfter(self::CACHE_TTL);
                
                $featuredEvents = $this->repository->findForRotation($limit);
                
                $this->logger->info('Fetched featured events for rotation', [
                    'count' => count($featuredEvents),
                    'limit' => $limit
                ]);
                
                return $featuredEvents;
            }
        );
    }

    /**
     * Get active featured events by display type
     */
    public function getActiveFeatures(?string $displayType = null): array
    {
        $cacheKey = self::CACHE_KEY_ACTIVE . ($displayType ? "_{$displayType}" : '');
        
        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($displayType) {
                $item->expiresAfter(self::CACHE_TTL);
                
                return $this->repository->findCurrentlyActive($displayType);
            }
        );
    }

    /**
     * Record a view for a featured event
     */
    public function recordView(int $featuredEventId): void
    {
        try {
            $featuredEvent = $this->repository->find($featuredEventId);
            if ($featuredEvent && $featuredEvent->isCurrentlyActive()) {
                $featuredEvent->incrementViewCount();
                $this->entityManager->flush();
                
                $this->logger->info('Recorded view for featured event', [
                    'featured_event_id' => $featuredEventId,
                    'new_view_count' => $featuredEvent->getViewCount()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to record view for featured event', [
                'featured_event_id' => $featuredEventId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record a click for a featured event
     */
    public function recordClick(int $featuredEventId): void
    {
        try {
            $featuredEvent = $this->repository->find($featuredEventId);
            if ($featuredEvent && $featuredEvent->isCurrentlyActive()) {
                $featuredEvent->incrementClickCount();
                $this->entityManager->flush();
                
                $this->logger->info('Recorded click for featured event', [
                    'featured_event_id' => $featuredEventId,
                    'new_click_count' => $featuredEvent->getClickCount(),
                    'ctr' => $featuredEvent->getClickThroughRate()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to record click for featured event', [
                'featured_event_id' => $featuredEventId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get rotation settings for JavaScript
     */
    public function getRotationSettings(array $featuredEvents): array
    {
        if (empty($featuredEvents)) {
            return [
                'autoRotate' => false,
                'rotationInterval' => 5000,
                'showControls' => false,
                'showIndicators' => false,
                'fadeEffect' => true
            ];
        }

        // Use settings from the first featured event, or defaults
        $firstEvent = $featuredEvents[0];
        $settings = $firstEvent->getDisplaySettings() ?? [];

        return array_merge([
            'autoRotate' => true,
            'rotationInterval' => 5000,
            'showControls' => true,
            'showIndicators' => true,
            'fadeEffect' => true
        ], $settings);
    }

    /**
     * Clear cache for featured events
     */
    public function clearCache(): void
    {
        try {
            $this->cache->delete(self::CACHE_KEY_ROTATION . '_limit_5');
            $this->cache->delete(self::CACHE_KEY_ACTIVE);
            $this->cache->delete(self::CACHE_KEY_ACTIVE . '_banner');
            $this->cache->delete(self::CACHE_KEY_ACTIVE . '_card');
            $this->cache->delete(self::CACHE_KEY_ACTIVE . '_sidebar');
            
            $this->logger->info('Cleared featured events cache');
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear featured events cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get top performing featured events
     */
    public function getTopPerforming(int $limit = 10): array
    {
        return $this->repository->findTopPerforming($limit);
    }

    /**
     * Get featured events expiring soon
     */
    public function getExpiringSoon(): array
    {
        return $this->repository->findExpiringSoon();
    }

    /**
     * Clean up expired featured events
     */
    public function cleanupExpired(): int
    {
        $deactivatedCount = $this->repository->deactivateExpired();
        
        if ($deactivatedCount > 0) {
            $this->clearCache();
            $this->logger->info('Deactivated expired featured events', [
                'count' => $deactivatedCount
            ]);
        }
        
        return $deactivatedCount;
    }

    /**
     * Prepare featured events for display with additional data
     */
    public function prepareForDisplay(array $featuredEvents): array
    {
        foreach ($featuredEvents as $featuredEvent) {
            // Record view automatically when preparing for display
            $this->recordView($featuredEvent->getId());
        }

        return $featuredEvents;
    }

    /**
     * Get featured event by ID with validation
     */
    public function getFeaturedEvent(int $id): ?FeaturedEvent
    {
        $featuredEvent = $this->repository->find($id);
        
        if ($featuredEvent && !$featuredEvent->isCurrentlyActive()) {
            return null; // Don't return inactive or expired events
        }
        
        return $featuredEvent;
    }

    /**
     * Create or update featured event (for admin use)
     */
    public function saveFeaturedEvent(FeaturedEvent $featuredEvent): void
    {
        $featuredEvent->setUpdatedAt(new \DateTime());
        
        $this->entityManager->persist($featuredEvent);
        $this->entityManager->flush();
        
        $this->clearCache();
        
        $this->logger->info('Saved featured event', [
            'featured_event_id' => $featuredEvent->getId(),
            'title' => $featuredEvent->getTitle(),
            'is_active' => $featuredEvent->isActive()
        ]);
    }

    /**
     * Delete featured event
     */
    public function deleteFeaturedEvent(FeaturedEvent $featuredEvent): void
    {
        $title = $featuredEvent->getTitle();
        $id = $featuredEvent->getId();
        
        $this->entityManager->remove($featuredEvent);
        $this->entityManager->flush();
        
        $this->clearCache();
        
        $this->logger->info('Deleted featured event', [
            'featured_event_id' => $id,
            'title' => $title
        ]);
    }
}