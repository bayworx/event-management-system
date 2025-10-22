<?php

namespace App\Service;

use App\Entity\Administrator;
use App\Entity\AgendaItem;
use App\Entity\Attendee;
use App\Entity\Event;
use App\Entity\EventImport;
use App\Entity\EventPresenter;
use App\Entity\Presenter;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventImportService
{
    private int $batchSize = 50;
    private int $processedCount = 0;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $doctrine,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger,
        private LoggerInterface $logger
    ) {}

    /**
     * Parse uploaded file and extract data for preview
     */
    public function parseFile(UploadedFile $file, string $importType): array
    {
        $this->logger->info('Starting file parsing', ['filename' => $file->getClientOriginalName(), 'type' => $importType]);

        try {
            // Create CSV reader
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $headers = $csv->getHeader();

            $data = [
                'headers' => $headers,
                'records' => array_slice($records, 0, 100), // Preview first 100 rows
                'total_rows' => count($records),
                'import_type' => $importType,
                'expected_columns' => $this->getExpectedColumns($importType),
                'mapping_suggestions' => $this->suggestColumnMapping($headers, $importType)
            ];

            $this->logger->info('File parsing completed', ['rows' => count($records), 'columns' => count($headers)]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('File parsing failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to parse file: ' . $e->getMessage());
        }
    }

    /**
     * Process the import based on type
     */
    public function processImport(EventImport $eventImport): void
    {
        $this->logger->info('Starting import process', ['import_id' => $eventImport->getId()]);
        $this->processedCount = 0;

        // Ensure we have a fresh EntityManager
        $this->ensureEntityManagerOpen();
        
        try {
            // Start a transaction for the entire import
            $this->entityManager->beginTransaction();
            
            $eventImport->setStatus('processing');
            $eventImport->setProcessedAt(new \DateTime());
            $this->entityManager->flush();
            
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->rollbackSafely();
            $this->handleImportError($eventImport, 'Failed to start import: ' . $e->getMessage());
            return;
        }

        try {
            $data = $eventImport->getImportedData();
            if (!$data) {
                throw new \RuntimeException('No import data found');
            }

            $results = match ($eventImport->getImportType()) {
                'complete' => $this->processCompleteImport($data, $eventImport),
                'events_only' => $this->processEventsImport($data, $eventImport),
                'attendees_only' => $this->processAttendeesImport($data, $eventImport),
                'agenda_only' => $this->processAgendaImport($data, $eventImport),
                'presenters_only' => $this->processPresentersImport($data, $eventImport),
                default => throw new \RuntimeException('Invalid import type')
            };

            // Update final results
            $this->ensureEntityManagerOpen();
            $this->entityManager->beginTransaction();
            
            $eventImport = $this->entityManager->find(EventImport::class, $eventImport->getId());
            $eventImport->setSuccessfulRows($results['successful']);
            $eventImport->setFailedRows($results['failed']);
            $eventImport->setResults($results['details']);
            $eventImport->setStatus('completed');
            
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Import completed successfully', [
                'import_id' => $eventImport->getId(),
                'successful' => $results['successful'],
                'failed' => $results['failed']
            ]);

        } catch (\Exception $e) {
            $this->rollbackSafely();
            $this->handleImportError($eventImport, 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Process complete import (events with agenda, presenters, and attendees)
     */
    private function processCompleteImport(array $data, EventImport $eventImport): array
    {
        $successful = 0;
        $failed = 0;
        $details = ['events' => [], 'agenda' => [], 'presenters' => [], 'attendees' => []];
        $totalRows = count($data['records']);

        foreach ($data['records'] as $index => $record) {
            $rowNum = $index + 2; // Account for header row
            
            try {
                $this->ensureEntityManagerOpen();
                $this->entityManager->beginTransaction();

                // Create event first
                $event = $this->createEventFromRecord($record, $rowNum);
                if ($event) {
                    $details['events'][] = "Row {$rowNum}: Created event '{$event->getTitle()}'";
                    
                    // Create agenda item if data exists
                    if ($this->hasAgendaData($record)) {
                        $agendaItem = $this->createAgendaItemFromRecord($record, $event, $rowNum);
                        if ($agendaItem) {
                            $details['agenda'][] = "Row {$rowNum}: Created agenda item for '{$event->getTitle()}'";
                        }
                    }

                    // Create presenter if data exists
                    if ($this->hasPresenterData($record)) {
                        $presenter = $this->createPresenterFromRecord($record, $event, $rowNum);
                        if ($presenter) {
                            $details['presenters'][] = "Row {$rowNum}: Created presenter for '{$event->getTitle()}'";
                        }
                    }

                    // Create attendee if data exists
                    if ($this->hasAttendeeData($record)) {
                        $attendee = $this->createAttendeeFromRecord($record, $event, $rowNum);
                        if ($attendee) {
                            $details['attendees'][] = "Row {$rowNum}: Created attendee for '{$event->getTitle()}'";
                        }
                    }
                }
                
                $this->entityManager->flush();
                $this->entityManager->commit();
                $successful++;
                
                // Batch processing and memory management
                $this->processedCount++;
                if ($this->processedCount % $this->batchSize === 0) {
                    $this->clearEntityManager();
                    $this->logger->debug("Processed {$this->processedCount}/{$totalRows} rows");
                }
                
            } catch (\Exception $e) {
                $this->rollbackSafely();
                $failed++;
                $errorMsg = "Failed to process row {$rowNum}: " . $e->getMessage();
                $details['errors'][] = $errorMsg;
                $this->logger->warning($errorMsg, ['error' => $e->getMessage(), 'row' => $rowNum]);
                
                // Continue with next record after error
                continue;
            }
        }

        return ['successful' => $successful, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Process events-only import
     */
    private function processEventsImport(array $data, EventImport $eventImport): array
    {
        $successful = 0;
        $failed = 0;
        $details = ['events' => [], 'errors' => []];
        $totalRows = count($data['records']);

        foreach ($data['records'] as $index => $record) {
            $rowNum = $index + 2;
            
            try {
                $this->ensureEntityManagerOpen();
                $this->entityManager->beginTransaction();
                
                $event = $this->createEventFromRecord($record, $rowNum);
                if ($event) {
                    $details['events'][] = "Row {$rowNum}: Created event '{$event->getTitle()}'";
                }
                
                $this->entityManager->flush();
                $this->entityManager->commit();
                $successful++;
                
                // Batch processing
                $this->processedCount++;
                if ($this->processedCount % $this->batchSize === 0) {
                    $this->clearEntityManager();
                    $this->logger->debug("Processed {$this->processedCount}/{$totalRows} rows");
                }
                
            } catch (\Exception $e) {
                $this->rollbackSafely();
                $failed++;
                $errorMsg = "Failed to process event row {$rowNum}: " . $e->getMessage();
                $details['errors'][] = $errorMsg;
                $this->logger->warning($errorMsg, ['error' => $e->getMessage(), 'row' => $rowNum]);
            }
        }

        return ['successful' => $successful, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Process attendees-only import
     */
    private function processAttendeesImport(array $data, EventImport $eventImport): array
    {
        $successful = 0;
        $failed = 0;
        $details = ['attendees' => [], 'errors' => []];
        $totalRows = count($data['records']);

        foreach ($data['records'] as $index => $record) {
            $rowNum = $index + 2;
            
            try {
                $this->ensureEntityManagerOpen();
                $this->entityManager->beginTransaction();
                
                // Find event by title or slug
                $event = $this->findEventByIdentifier($record['event_title'] ?? $record['Event'] ?? '');
                if (!$event) {
                    throw new \RuntimeException('Event not found');
                }

                $attendee = $this->createAttendeeFromRecord($record, $event, $rowNum);
                if ($attendee) {
                    $details['attendees'][] = "Row {$rowNum}: Created attendee '{$attendee->getName()}' for '{$event->getTitle()}'";
                }
                
                $this->entityManager->flush();
                $this->entityManager->commit();
                $successful++;
                
                // Batch processing
                $this->processedCount++;
                if ($this->processedCount % $this->batchSize === 0) {
                    $this->clearEntityManager();
                    $this->logger->debug("Processed {$this->processedCount}/{$totalRows} rows");
                }
                
            } catch (\Exception $e) {
                $this->rollbackSafely();
                $failed++;
                $errorMsg = "Failed to process attendee row {$rowNum}: " . $e->getMessage();
                $details['errors'][] = $errorMsg;
                $this->logger->warning($errorMsg, ['error' => $e->getMessage(), 'row' => $rowNum]);
            }
        }

        return ['successful' => $successful, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Process agenda-only import
     */
    private function processAgendaImport(array $data, EventImport $eventImport): array
    {
        $successful = 0;
        $failed = 0;
        $details = ['agenda' => [], 'errors' => []];
        $totalRows = count($data['records']);

        foreach ($data['records'] as $index => $record) {
            $rowNum = $index + 2;
            
            try {
                $this->ensureEntityManagerOpen();
                $this->entityManager->beginTransaction();
                
                // Find event by title or slug
                $event = $this->findEventByIdentifier($record['event_title'] ?? $record['Event'] ?? '');
                if (!$event) {
                    throw new \RuntimeException('Event not found');
                }

                $agendaItem = $this->createAgendaItemFromRecord($record, $event, $rowNum);
                if ($agendaItem) {
                    $details['agenda'][] = "Row {$rowNum}: Created agenda item '{$agendaItem->getTitle()}' for '{$event->getTitle()}'";
                }
                
                $this->entityManager->flush();
                $this->entityManager->commit();
                $successful++;
                
                // Batch processing
                $this->processedCount++;
                if ($this->processedCount % $this->batchSize === 0) {
                    $this->clearEntityManager();
                    $this->logger->debug("Processed {$this->processedCount}/{$totalRows} rows");
                }
                
            } catch (\Exception $e) {
                $this->rollbackSafely();
                $failed++;
                $errorMsg = "Failed to process agenda row {$rowNum}: " . $e->getMessage();
                $details['errors'][] = $errorMsg;
                $this->logger->warning($errorMsg, ['error' => $e->getMessage(), 'row' => $rowNum]);
            }
        }

        return ['successful' => $successful, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Process presenters-only import
     */
    private function processPresentersImport(array $data, EventImport $eventImport): array
    {
        $successful = 0;
        $failed = 0;
        $details = ['presenters' => [], 'errors' => []];
        $totalRows = count($data['records']);

        foreach ($data['records'] as $index => $record) {
            $rowNum = $index + 2;
            
            try {
                $this->ensureEntityManagerOpen();
                $this->entityManager->beginTransaction();
                
                // Find event by title or slug
                $event = $this->findEventByIdentifier($record['event_title'] ?? $record['Event'] ?? '');
                if (!$event) {
                    throw new \RuntimeException('Event not found');
                }

                $presenter = $this->createPresenterFromRecord($record, $event, $rowNum);
                if ($presenter) {
                    $details['presenters'][] = "Row {$rowNum}: Created presenter '{$presenter->getName()}' for '{$event->getTitle()}'";
                }
                
                $this->entityManager->flush();
                $this->entityManager->commit();
                $successful++;
                
                // Batch processing
                $this->processedCount++;
                if ($this->processedCount % $this->batchSize === 0) {
                    $this->clearEntityManager();
                    $this->logger->debug("Processed {$this->processedCount}/{$totalRows} rows");
                }
                
            } catch (\Exception $e) {
                $this->rollbackSafely();
                $failed++;
                $errorMsg = "Failed to process presenter row {$rowNum}: " . $e->getMessage();
                $details['errors'][] = $errorMsg;
                $this->logger->warning($errorMsg, ['error' => $e->getMessage(), 'row' => $rowNum]);
            }
        }

        return ['successful' => $successful, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Create event from CSV record
     */
    private function createEventFromRecord(array $record, int $rowNum): ?Event
    {
        $title = $record['title'] ?? $record['event_title'] ?? $record['Event Title'] ?? $record['Event'] ?? '';
        if (empty($title)) {
            throw new \RuntimeException("Row {$rowNum}: Event title is required");
        }

        // Check if event already exists
        $existingEvent = $this->entityManager->getRepository(Event::class)->findOneBy(['title' => $title]);
        if ($existingEvent) {
            return $existingEvent; // Return existing event instead of creating duplicate
        }

        $event = new Event();
        $event->setTitle($title);
        $event->setSlug($this->slugger->slug($title)->lower());
        
        // Set description
        $description = $record['description'] ?? $record['Description'] ?? '';
        if ($description) {
            $event->setDescription($description);
        }

        // Set dates
        $startDate = $this->parseDate($record['start_date'] ?? $record['Start Date'] ?? '');
        if ($startDate) {
            $event->setStartDate($startDate);
        } else {
            throw new \RuntimeException("Row {$rowNum}: Valid start date is required");
        }

        $endDate = $this->parseDate($record['end_date'] ?? $record['End Date'] ?? '');
        if ($endDate) {
            $event->setEndDate($endDate);
        }

        // Set location
        $location = $record['location'] ?? $record['Location'] ?? '';
        if ($location) {
            $event->setLocation($location);
        }

        // Set max attendees
        $maxAttendees = $record['max_attendees'] ?? $record['Max Attendees'] ?? '';
        if ($maxAttendees && is_numeric($maxAttendees)) {
            $event->setMaxAttendees((int)$maxAttendees);
        }

        $this->entityManager->persist($event);
        return $event;
    }

    /**
     * Create attendee from CSV record
     */
    private function createAttendeeFromRecord(array $record, Event $event, int $rowNum): ?Attendee
    {
        $name = $record['attendee_name'] ?? $record['name'] ?? $record['Name'] ?? '';
        $email = $record['attendee_email'] ?? $record['email'] ?? $record['Email'] ?? '';

        if (empty($name) || empty($email)) {
            return null; // Skip if no name or email
        }

        // Check if attendee already exists for this event
        $existingAttendee = $this->entityManager->getRepository(Attendee::class)
            ->findOneBy(['email' => $email, 'event' => $event]);
        if ($existingAttendee) {
            return $existingAttendee;
        }

        $attendee = new Attendee();
        $attendee->setName($name);
        $attendee->setEmail($email);
        $attendee->setEvent($event);

        // Set optional fields
        if (!empty($record['phone'] ?? $record['Phone'] ?? '')) {
            $attendee->setPhone($record['phone'] ?? $record['Phone']);
        }

        if (!empty($record['organization'] ?? $record['Organization'] ?? '')) {
            $attendee->setOrganization($record['organization'] ?? $record['Organization']);
        }

        if (!empty($record['job_title'] ?? $record['Job Title'] ?? '')) {
            $attendee->setJobTitle($record['job_title'] ?? $record['Job Title']);
        }

        // Generate a temporary password
        $tempPassword = bin2hex(random_bytes(8));
        $attendee->setPassword($this->passwordHasher->hashPassword($attendee, $tempPassword));

        $this->entityManager->persist($attendee);
        return $attendee;
    }

    /**
     * Create agenda item from CSV record
     */
    private function createAgendaItemFromRecord(array $record, Event $event, int $rowNum): ?AgendaItem
    {
        $title = $record['agenda_title'] ?? $record['agenda_item'] ?? $record['Agenda Item'] ?? '';
        if (empty($title)) {
            return null; // Skip if no agenda title
        }

        $agendaItem = new AgendaItem();
        $agendaItem->setTitle($title);
        $agendaItem->setEvent($event);

        // Set description
        $description = $record['agenda_description'] ?? $record['Agenda Description'] ?? '';
        if ($description) {
            $agendaItem->setDescription($description);
        }

        // Set start time (required field)
        $startTime = $this->parseDateTime($record['agenda_start'] ?? $record['Start Time'] ?? '');
        if ($startTime) {
            $agendaItem->setStartTime($startTime);
        } else {
            // If no start time provided, use the event's start time as default
            $agendaItem->setStartTime($event->getStartDate() ?? new \DateTime());
        }

        // Set end time
        $endTime = $this->parseDateTime($record['agenda_end'] ?? $record['End Time'] ?? '');
        if ($endTime) {
            $agendaItem->setEndTime($endTime);
        }

        // Set item type (required field)
        $itemType = $record['item_type'] ?? $record['Item Type'] ?? $record['Type'] ?? 'session';
        $validTypes = ['session', 'break', 'lunch', 'keynote', 'workshop', 'networking', 'other'];
        if (in_array($itemType, $validTypes)) {
            $agendaItem->setItemType($itemType);
        } else {
            $agendaItem->setItemType('session'); // Default to session
        }

        // Set speaker
        $speaker = $record['speaker'] ?? $record['Speaker'] ?? '';
        if ($speaker) {
            $agendaItem->setSpeaker($speaker);
        }

        // Set location
        $location = $record['agenda_location'] ?? $record['Agenda Location'] ?? '';
        if ($location) {
            $agendaItem->setLocation($location);
        }

        // Set sort order
        $sortOrder = $record['sort_order'] ?? $record['Sort Order'] ?? '';
        if ($sortOrder && is_numeric($sortOrder)) {
            $agendaItem->setSortOrder((int)$sortOrder);
        }

        $this->entityManager->persist($agendaItem);
        return $agendaItem;
    }

    /**
     * Create presenter from CSV record
     */
    private function createPresenterFromRecord(array $record, Event $event, int $rowNum): ?EventPresenter
    {
        $name = $record['presenter_name'] ?? $record['presenter'] ?? $record['Presenter'] ?? '';
        if (empty($name)) {
            return null; // Skip if no presenter name
        }

        // Find or create presenter
        $presenter = $this->entityManager->getRepository(Presenter::class)->findOneBy(['name' => $name]);
        if (!$presenter) {
            $presenter = new Presenter();
            $presenter->setName($name);
            
            $bio = $record['presenter_bio'] ?? $record['Bio'] ?? '';
            if ($bio) {
                $presenter->setBio($bio);
            }

            $email = $record['presenter_email'] ?? $record['Presenter Email'] ?? '';
            if ($email) {
                $presenter->setEmail($email);
            }

            $this->entityManager->persist($presenter);
        }

        // Create event presenter relationship
        $eventPresenter = new EventPresenter();
        $eventPresenter->setEvent($event);
        $eventPresenter->setPresenter($presenter);

        $role = $record['presenter_role'] ?? $record['Role'] ?? '';
        if ($role) {
            $eventPresenter->setRole($role);
        }

        $sortOrder = $record['presenter_order'] ?? $record['Presenter Order'] ?? '';
        if ($sortOrder && is_numeric($sortOrder)) {
            $eventPresenter->setSortOrder((int)$sortOrder);
        }

        $this->entityManager->persist($eventPresenter);
        return $eventPresenter;
    }

    /**
     * Find event by title or slug
     */
    private function findEventByIdentifier(string $identifier): ?Event
    {
        if (empty($identifier)) {
            return null;
        }

        $event = $this->entityManager->getRepository(Event::class)->findOneBy(['title' => $identifier]);
        if (!$event) {
            $event = $this->entityManager->getRepository(Event::class)->findOneBy(['slug' => $this->slugger->slug($identifier)->lower()]);
        }

        return $event;
    }

    /**
     * Check if record has agenda data
     */
    private function hasAgendaData(array $record): bool
    {
        return !empty($record['agenda_title'] ?? $record['agenda_item'] ?? $record['Agenda Item'] ?? '');
    }

    /**
     * Check if record has presenter data
     */
    private function hasPresenterData(array $record): bool
    {
        return !empty($record['presenter_name'] ?? $record['presenter'] ?? $record['Presenter'] ?? '');
    }

    /**
     * Check if record has attendee data
     */
    private function hasAttendeeData(array $record): bool
    {
        return !empty($record['attendee_name'] ?? $record['name'] ?? $record['Name'] ?? '') &&
               !empty($record['attendee_email'] ?? $record['email'] ?? $record['Email'] ?? '');
    }

    /**
     * Parse date from string
     */
    private function parseDate(string $dateString): ?\DateTime
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse date', ['date' => $dateString]);
            return null;
        }
    }

    /**
     * Parse datetime from string
     */
    private function parseDateTime(string $dateTimeString): ?\DateTime
    {
        if (empty($dateTimeString)) {
            return null;
        }

        try {
            return new \DateTime($dateTimeString);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse datetime', ['datetime' => $dateTimeString]);
            return null;
        }
    }

    /**
     * Get expected columns for import type
     */
    private function getExpectedColumns(string $importType): array
    {
        return match ($importType) {
            'complete' => [
                'Event' => ['title', 'event_title', 'Event Title', 'Event'],
                'Description' => ['description', 'Description'],
                'Start Date' => ['start_date', 'Start Date'],
                'End Date' => ['end_date', 'End Date'],
                'Location' => ['location', 'Location'],
                'Attendee Name' => ['attendee_name', 'name', 'Name'],
                'Attendee Email' => ['attendee_email', 'email', 'Email'],
                'Agenda Item' => ['agenda_title', 'agenda_item', 'Agenda Item'],
                'Agenda Start Time' => ['agenda_start', 'Start Time'],
                'Item Type' => ['item_type', 'Item Type', 'Type'],
                'Presenter' => ['presenter_name', 'presenter', 'Presenter'],
            ],
            'events_only' => [
                'Event Title' => ['title', 'event_title', 'Event Title', 'Event'],
                'Description' => ['description', 'Description'],
                'Start Date' => ['start_date', 'Start Date'],
                'End Date' => ['end_date', 'End Date'],
                'Location' => ['location', 'Location'],
                'Max Attendees' => ['max_attendees', 'Max Attendees'],
            ],
            'attendees_only' => [
                'Event' => ['event_title', 'Event'],
                'Name' => ['name', 'attendee_name', 'Name'],
                'Email' => ['email', 'attendee_email', 'Email'],
                'Phone' => ['phone', 'Phone'],
                'Organization' => ['organization', 'Organization'],
                'Job Title' => ['job_title', 'Job Title'],
            ],
            'agenda_only' => [
                'Event' => ['event_title', 'Event'],
                'Agenda Item' => ['agenda_title', 'agenda_item', 'Agenda Item'],
                'Description' => ['agenda_description', 'Agenda Description'],
                'Start Time' => ['agenda_start', 'Start Time'],
                'End Time' => ['agenda_end', 'End Time'],
                'Item Type' => ['item_type', 'Item Type', 'Type'],
                'Speaker' => ['speaker', 'Speaker'],
                'Location' => ['agenda_location', 'Agenda Location'],
            ],
            'presenters_only' => [
                'Event' => ['event_title', 'Event'],
                'Presenter' => ['presenter_name', 'presenter', 'Presenter'],
                'Bio' => ['presenter_bio', 'Bio'],
                'Email' => ['presenter_email', 'Presenter Email'],
                'Role' => ['presenter_role', 'Role'],
            ],
            default => []
        };
    }

    /**
     * Suggest column mapping based on header names
     */
    private function suggestColumnMapping(array $headers, string $importType): array
    {
        $expectedColumns = $this->getExpectedColumns($importType);
        $suggestions = [];

        foreach ($expectedColumns as $expectedField => $variations) {
            $bestMatch = null;
            $highestScore = 0;

            foreach ($headers as $header) {
                foreach ($variations as $variation) {
                    $score = $this->calculateSimilarity($header, $variation);
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestMatch = $header;
                    }
                }
            }

            if ($bestMatch && $highestScore > 0.7) { // Only suggest if similarity > 70%
                $suggestions[$expectedField] = $bestMatch;
            }
        }

        return $suggestions;
    }

    /**
     * Calculate similarity between two strings
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text(strtolower($str1), strtolower($str2), $percent);
        return $percent / 100;
    }

    /**
     * Generate CSV template for import type
     */
    public function generateTemplate(string $importType): string
    {
        $expectedColumns = $this->getExpectedColumns($importType);
        $headers = array_keys($expectedColumns);

        // Create sample data
        $sampleData = match ($importType) {
            'complete' => [
                'Annual Conference', 'Our annual technology conference', '2024-06-15 09:00:00', '2024-06-15 17:00:00', 
                'Convention Center', 'John Doe', 'john@example.com', 'Opening Keynote', 'Jane Smith'
            ],
            'events_only' => [
                'Annual Conference', 'Our annual technology conference', '2024-06-15 09:00:00', '2024-06-15 17:00:00', 
                'Convention Center', '100'
            ],
            'attendees_only' => [
                'Annual Conference', 'John Doe', 'john@example.com', '555-1234', 'Acme Corp', 'Developer'
            ],
            'agenda_only' => [
                'Annual Conference', 'Opening Keynote', 'Welcome and introduction', '09:00:00', '10:00:00'
            ],
            'presenters_only' => [
                'Annual Conference', 'Jane Smith', 'Technology expert with 10 years experience', 'jane@example.com', 'Keynote Speaker'
            ],
            default => []
        };

        $csv = implode(',', $headers) . "\n";
        if (!empty($sampleData)) {
            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $sampleData));
        }

        return $csv;
    }

    /**
     * Ensure EntityManager is open, reset if closed
     */
    private function ensureEntityManagerOpen(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->logger->warning('EntityManager was closed, creating new one');
            $this->entityManager = $this->doctrine->resetManager();
        }
    }

    /**
     * Clear EntityManager to prevent memory leaks
     */
    private function clearEntityManager(): void
    {
        try {
            $this->entityManager->clear();
            if (gc_collect_cycles()) {
                $this->logger->debug('Garbage collection performed');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to clear EntityManager', ['error' => $e->getMessage()]);
            $this->ensureEntityManagerOpen();
        }
    }

    /**
     * Safely rollback transaction if active
     */
    private function rollbackSafely(): void
    {
        try {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to rollback transaction', ['error' => $e->getMessage()]);
            // Force a new EntityManager if rollback fails
            $this->entityManager = $this->doctrine->resetManager();
        }
    }

    /**
     * Handle import errors consistently
     */
    private function handleImportError(EventImport $eventImport, string $errorMessage): void
    {
        try {
            $importId = $eventImport->getId();
            $this->ensureEntityManagerOpen();
            $this->entityManager->beginTransaction();
            
            // Find fresh entity to ensure we have latest state
            $eventImport = $this->entityManager->find(EventImport::class, $importId);
            if ($eventImport) {
                $eventImport->setStatus('failed');
                $eventImport->addError($errorMessage);
                $this->entityManager->flush();
                $this->entityManager->commit();
            }
            
            $this->logger->error('Import error handled', [
                'import_id' => $importId,
                'error' => $errorMessage
            ]);
            
        } catch (\Exception $e) {
            $this->rollbackSafely();
            $this->logger->critical('Failed to handle import error', [
                'import_id' => $eventImport->getId() ?? 'unknown',
                'original_error' => $errorMessage,
                'handling_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Set batch size for processing (useful for testing or large imports)
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = max(1, $batchSize); // Ensure at least 1
    }
}
