<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarSubscriptionController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository
    ) {
    }

    #[Route('/calendar/subscribe.ics', name: 'calendar_subscribe', methods: ['GET'])]
    public function subscribe(): Response
    {
        // Get all active upcoming events
        $events = $this->eventRepository->findUpcoming();

        // Generate ICS calendar content
        $icsContent = $this->generateIcsContent($events);

        // Create response with proper ICS headers
        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="events.ics"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/calendar/feed.ics', name: 'calendar_feed', methods: ['GET'])]
    public function feed(): Response
    {
        // Get all active events (both past and future for feed)
        $events = $this->eventRepository->findBy(
            ['isActive' => true],
            ['startDate' => 'ASC']
        );

        // Generate ICS calendar content
        $icsContent = $this->generateIcsContent($events, true);

        // Create response with proper ICS headers for feed
        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
        
        return $response;
    }

    private function generateIcsContent(array $events, bool $isFeed = false): string
    {
        $baseUrl = $this->generateUrl('events_list', [], true);
        $companyConfig = $this->container->get('App\Service\ConfigurationService')->getCompanyInfo();
        $companyName = $companyConfig['name'] ?: 'Event Management System';

        $ics = [];
        $ics[] = 'BEGIN:VCALENDAR';
        $ics[] = 'VERSION:2.0';
        $ics[] = 'PRODID:-//' . $companyName . '//Event Management System//EN';
        $ics[] = 'CALSCALE:GREGORIAN';
        $ics[] = 'METHOD:PUBLISH';
        $ics[] = 'X-WR-CALNAME:' . $companyName . ' Events';
        $ics[] = 'X-WR-CALDESC:Events from ' . $companyName;
        $ics[] = 'X-WR-TIMEZONE:UTC';
        
        if ($isFeed) {
            $feedUrl = $this->generateUrl('calendar_feed', [], true);
            $ics[] = 'X-WR-CALREFRESH:PT1H'; // Refresh every hour
            $ics[] = 'X-PUBLISHED-TTL:PT1H';
        }

        foreach ($events as $event) {
            $ics[] = 'BEGIN:VEVENT';
            
            // Generate unique ID for the event
            $uid = 'event-' . $event->getId() . '@' . parse_url($baseUrl, PHP_URL_HOST);
            $ics[] = 'UID:' . $uid;
            
            // Event details
            $ics[] = 'SUMMARY:' . $this->escapeIcsText($event->getTitle());
            $ics[] = 'DTSTART:' . $event->getStartDate()->format('Ymd\THis\Z');
            
            if ($event->getEndDate()) {
                $ics[] = 'DTEND:' . $event->getEndDate()->format('Ymd\THis\Z');
            } else {
                // If no end date, assume 1 hour duration
                $endDate = clone $event->getStartDate();
                $endDate->add(new \DateInterval('PT1H'));
                $ics[] = 'DTEND:' . $endDate->format('Ymd\THis\Z');
            }
            
            if ($event->getLocation()) {
                $ics[] = 'LOCATION:' . $this->escapeIcsText($event->getLocation());
            }
            
            if ($event->getDescription()) {
                $ics[] = 'DESCRIPTION:' . $this->escapeIcsText($event->getDescription());
            }
            
            // Add event URL
            $eventUrl = $this->generateUrl('event_show', ['slug' => $event->getSlug()], true);
            $ics[] = 'URL:' . $eventUrl;
            
            // Add timestamps
            $now = new \DateTime();
            $ics[] = 'DTSTAMP:' . $now->format('Ymd\THis\Z');
            $ics[] = 'CREATED:' . $event->getCreatedAt()->format('Ymd\THis\Z');
            $ics[] = 'LAST-MODIFIED:' . $event->getUpdatedAt()->format('Ymd\THis\Z');
            
            // Add status
            if ($event->getStartDate() < $now) {
                $ics[] = 'STATUS:CONFIRMED';
            } else {
                $ics[] = 'STATUS:CONFIRMED';
            }
            
            // Add categories
            $ics[] = 'CATEGORIES:EVENT';
            
            // Add organizer info
            if ($companyConfig['email']) {
                $ics[] = 'ORGANIZER;CN=' . $this->escapeIcsText($companyName) . ':mailto:' . $companyConfig['email'];
            }
            
            $ics[] = 'END:VEVENT';
        }

        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics);
    }

    private function escapeIcsText(string $text): string
    {
        // Escape special characters for ICS format
        $text = str_replace(['\\', ',', ';', "\n", "\r"], ['\\\\', '\\,', '\\;', '\\n', '\\r'], $text);
        
        // Remove any remaining line breaks and trim
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Limit length to prevent issues
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 997) . '...';
        }
        
        return $text;
    }
}