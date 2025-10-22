<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SingleEventIcsController extends AbstractController
{
    #[Route('/event/{slug}/calendar.ics', name: 'event_calendar_download', methods: ['GET'])]
    public function downloadSingleEvent(Event $event): Response
    {
        // Generate ICS content for single event
        $icsContent = $this->generateSingleEventIcs($event);

        // Create response with proper ICS headers
        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->sanitizeFilename($event->getTitle()) . '.ics"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function generateSingleEventIcs(Event $event): string
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
        $ics[] = 'STATUS:CONFIRMED';
        
        // Add categories
        $ics[] = 'CATEGORIES:EVENT';
        
        // Add organizer info
        if ($companyConfig['email']) {
            $ics[] = 'ORGANIZER;CN=' . $this->escapeIcsText($companyName) . ':mailto:' . $companyConfig['email'];
        }
        
        // Add alarm for 1 hour before
        $ics[] = 'BEGIN:VALARM';
        $ics[] = 'TRIGGER:-PT1H';
        $ics[] = 'ACTION:DISPLAY';
        $ics[] = 'DESCRIPTION:Reminder: ' . $this->escapeIcsText($event->getTitle()) . ' starts in 1 hour';
        $ics[] = 'END:VALARM';
        
        $ics[] = 'END:VEVENT';
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

    private function sanitizeFilename(string $filename): string
    {
        // Remove special characters from filename
        $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
        $filename = preg_replace('/\s+/', '_', trim($filename));
        $filename = substr($filename, 0, 50); // Limit length
        
        return $filename ?: 'event';
    }
}