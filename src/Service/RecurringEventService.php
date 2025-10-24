<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class RecurringEventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Generate recurring event instances based on the parent event's recurrence settings
     */
    public function generateRecurringInstances(Event $parentEvent): array
    {
        if (!$parentEvent->isRecurring()) {
            return [];
        }

        $pattern = $parentEvent->getRecurrencePattern();
        $interval = $parentEvent->getRecurrenceInterval() ?? 1;
        $endDate = $parentEvent->getRecurrenceEndDate();
        $count = $parentEvent->getRecurrenceCount();

        if (!$pattern || (!$endDate && !$count)) {
            throw new \InvalidArgumentException('Recurrence pattern and either end date or count must be specified');
        }

        $instances = [];
        $currentDate = clone $parentEvent->getStartDate();
        $eventDuration = null;
        
        if ($parentEvent->getEndDate()) {
            $eventDuration = $currentDate->diff($parentEvent->getEndDate());
        }

        $instanceCount = 0;
        $maxInstances = $count ?? 100; // Safety limit if using end date

        while ($instanceCount < $maxInstances) {
            // Calculate next occurrence date
            $currentDate = $this->calculateNextOccurrence($currentDate, $pattern, $interval);
            
            // Check if we've exceeded the end date
            if ($endDate && $currentDate > $endDate) {
                break;
            }

            // Check if we've reached the count limit
            if ($count && $instanceCount >= $count) {
                break;
            }

            // Create new event instance
            $instance = $this->createEventInstance($parentEvent, $currentDate, $eventDuration, $instanceCount + 1);
            $instances[] = $instance;
            
            $instanceCount++;
        }

        return $instances;
    }

    /**
     * Calculate the next occurrence date based on pattern and interval
     */
    private function calculateNextOccurrence(\DateTimeInterface $currentDate, string $pattern, int $interval): \DateTime
    {
        $nextDate = \DateTime::createFromInterface($currentDate);
        
        switch ($pattern) {
            case 'daily':
                $nextDate->modify("+{$interval} days");
                break;
            case 'weekly':
                $nextDate->modify("+{$interval} weeks");
                break;
            case 'monthly':
                $nextDate->modify("+{$interval} months");
                break;
            case 'yearly':
                $nextDate->modify("+{$interval} years");
                break;
            default:
                throw new \InvalidArgumentException("Invalid recurrence pattern: {$pattern}");
        }

        return $nextDate;
    }

    /**
     * Create a single recurring event instance
     */
    private function createEventInstance(Event $parentEvent, \DateTime $startDate, ?\DateInterval $duration, int $sequence): Event
    {
        $instance = new Event();
        
        // Copy basic properties from parent
        $instance->setTitle($parentEvent->getTitle());
        $instance->setDescription($parentEvent->getDescription());
        $instance->setLocation($parentEvent->getLocation());
        $instance->setMaxAttendees($parentEvent->getMaxAttendees());
        $instance->setIsActive($parentEvent->isActive());
        $instance->setBannerImage($parentEvent->getBannerImage());
        
        // Set dates
        $instance->setStartDate(clone $startDate);
        if ($duration) {
            $endDate = clone $startDate;
            $endDate->add($duration);
            $instance->setEndDate($endDate);
        }
        
        // Link to parent event
        $instance->setParentEvent($parentEvent);
        
        // Generate unique slug
        $baseSlug = $this->slugger->slug($parentEvent->getTitle())->lower();
        $slug = $baseSlug . '-' . $startDate->format('Y-m-d') . '-' . $sequence;
        $instance->setSlug($slug);
        
        // Copy administrators
        foreach ($parentEvent->getAdministrators() as $admin) {
            $instance->addAdministrator($admin);
        }
        
        // Copy agenda items
        foreach ($parentEvent->getAgendaItems() as $agendaItem) {
            $newAgendaItem = clone $agendaItem;
            $newAgendaItem->setEvent($instance);
            $instance->addAgendaItem($newAgendaItem);
        }
        
        // Copy presenters
        foreach ($parentEvent->getEventPresenters() as $eventPresenter) {
            $newEventPresenter = clone $eventPresenter;
            $newEventPresenter->setEvent($instance);
            $instance->addEventPresenter($newEventPresenter);
        }
        
        return $instance;
    }

    /**
     * Delete all child events of a recurring series
     */
    public function deleteRecurringInstances(Event $parentEvent): int
    {
        $count = 0;
        $childEvents = $parentEvent->getChildEvents()->toArray();
        
        foreach ($childEvents as $childEvent) {
            $this->entityManager->remove($childEvent);
            $count++;
        }
        
        return $count;
    }

    /**
     * Regenerate recurring instances (delete old ones and create new ones)
     */
    public function regenerateRecurringInstances(Event $parentEvent): array
    {
        // Delete existing instances
        $this->deleteRecurringInstances($parentEvent);
        $this->entityManager->flush();
        
        // Generate new instances
        $instances = $this->generateRecurringInstances($parentEvent);
        
        foreach ($instances as $instance) {
            $this->entityManager->persist($instance);
        }
        
        return $instances;
    }
}
