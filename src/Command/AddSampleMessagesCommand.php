<?php

namespace App\Command;

use App\Entity\Attendee;
use App\Entity\Administrator;
use App\Entity\Event;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-sample-messages',
    description: 'Add sample messages from attendees to administrators for testing',
)]
class AddSampleMessagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get events with attendees
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        if (empty($events)) {
            $io->error('No events found. Please create events first.');
            return Command::FAILURE;
        }

        $messagesCreated = 0;

        foreach ($events as $event) {
            // Get attendees for this event
            $attendees = $event->getAttendees();
            
            if ($attendees->isEmpty()) {
                $io->warning(sprintf('Event "%s" has no attendees. Skipping...', $event->getTitle()));
                continue;
            }

            // Get administrators for this event
            $administrators = $event->getAdministrators();
            
            if ($administrators->isEmpty()) {
                $io->warning(sprintf('Event "%s" has no administrators. Skipping...', $event->getTitle()));
                continue;
            }

            $attendee = $attendees->first();
            $admin = $administrators->first();

            // Sample messages based on event type
            $sampleMessages = $this->getSampleMessagesForEvent($event);

            foreach ($sampleMessages as $messageData) {
                $message = new Message();
                $message->setSubject($messageData['subject']);
                $message->setContent($messageData['content']);
                $message->setSender($attendee);
                $message->setRecipient($admin);
                $message->setEvent($event);
                $message->setPriority($messageData['priority'] ?? 'normal');
                $message->setSentAt(new \DateTime($messageData['sent_at'] ?? 'now'));
                
                if (isset($messageData['is_read']) && $messageData['is_read']) {
                    $message->setIsRead(true);
                }

                $this->entityManager->persist($message);
                $messagesCreated++;
            }
        }

        if ($messagesCreated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Created %d sample messages successfully!', $messagesCreated));
        } else {
            $io->warning('No messages were created. Make sure events have both attendees and administrators.');
        }

        return Command::SUCCESS;
    }

    private function getSampleMessagesForEvent(Event $event): array
    {
        $eventTitle = strtolower($event->getTitle());
        
        // Customize messages based on event type
        if (str_contains($eventTitle, 'workshop') || str_contains($eventTitle, 'development')) {
            return [
                [
                    'subject' => 'Question about workshop materials',
                    'content' => 'Hi, I was wondering if the workshop materials will be made available before the event? I\'d like to review them in advance. Also, will there be hands-on coding exercises? Thanks!',
                    'priority' => 'normal',
                    'sent_at' => '-3 days',
                ],
                [
                    'subject' => 'Dietary restrictions',
                    'content' => 'Hello, I have a severe nut allergy. Will lunch be provided during the workshop? If so, could you please ensure there are nut-free options available? Thank you for your help!',
                    'priority' => 'high',
                    'sent_at' => '-1 day',
                    'is_read' => true,
                ],
            ];
        } elseif (str_contains($eventTitle, 'conference') || str_contains($eventTitle, 'summit')) {
            return [
                [
                    'subject' => 'Speaker lineup question',
                    'content' => 'Are the final speaker schedules available yet? I\'m particularly interested in the afternoon sessions and want to plan my attendance accordingly.',
                    'priority' => 'normal',
                    'sent_at' => '-5 days',
                    'is_read' => true,
                ],
                [
                    'subject' => 'Parking information needed',
                    'content' => 'I\'ll be driving to the conference. Is there parking available at the venue? If not, can you recommend nearby parking facilities? Also, what time do doors open?',
                    'priority' => 'normal',
                    'sent_at' => '-2 days',
                ],
                [
                    'subject' => 'Networking opportunities',
                    'content' => 'Will there be dedicated networking sessions or mixers? I\'m hoping to connect with other attendees and speakers in my industry.',
                    'priority' => 'low',
                    'sent_at' => '-6 days',
                    'is_read' => true,
                ],
            ];
        } elseif (str_contains($eventTitle, 'meetup')) {
            return [
                [
                    'subject' => 'First time attendee',
                    'content' => 'This will be my first time attending this meetup. What should I expect? Is it more lecture-style or discussion-based? Looking forward to it!',
                    'priority' => 'normal',
                    'sent_at' => '-2 days',
                ],
            ];
        } elseif (str_contains($eventTitle, 'design')) {
            return [
                [
                    'subject' => 'Software requirements',
                    'content' => 'Do I need to bring my own laptop? If so, what design software should I have installed? I use Figma and Sketch - will those work for the exercises?',
                    'priority' => 'normal',
                    'sent_at' => '-4 days',
                    'is_read' => true,
                ],
                [
                    'subject' => 'Portfolio review opportunity?',
                    'content' => 'I saw that there might be portfolio reviews. Is this still happening? I\'d love to get feedback from the instructors on my recent work.',
                    'priority' => 'low',
                    'sent_at' => '-1 day',
                ],
            ];
        } else {
            // Generic messages
            return [
                [
                    'subject' => 'Registration confirmation',
                    'content' => 'I registered for this event but haven\'t received a confirmation email. Can you please confirm that I\'m on the attendee list? My registration email was ' . $event->getAttendees()->first()->getEmail(),
                    'priority' => 'high',
                    'sent_at' => '-3 days',
                ],
                [
                    'subject' => 'Event details inquiry',
                    'content' => 'Could you provide more information about what to expect at this event? I\'m very interested but want to make sure it\'s a good fit for my schedule and interests.',
                    'priority' => 'normal',
                    'sent_at' => '-5 days',
                    'is_read' => true,
                ],
            ];
        }
    }
}
