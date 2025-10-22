<?php

namespace App\Command;

use App\Entity\Administrator;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:create-sample-data',
    description: 'Create sample events and data for testing',
)]
class CreateSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find an admin to assign events to
        $admin = $this->entityManager->getRepository(Administrator::class)
            ->findOneBy(['isSuperAdmin' => true]);

        if (!$admin) {
            $io->error('No administrator found. Please create an admin user first using app:create-admin');
            return Command::FAILURE;
        }

        $sampleEvents = [
            [
                'title' => 'Symfony Development Workshop',
                'description' => 'Learn modern PHP web development with Symfony framework. This hands-on workshop covers controllers, templates, database integration, and security best practices.',
                'startDate' => new \DateTime('+1 week'),
                'endDate' => new \DateTime('+1 week +6 hours'),
                'location' => 'Tech Conference Center, San Francisco',
                'maxAttendees' => 50,
            ],
            [
                'title' => 'Digital Marketing Summit 2024',
                'description' => 'Join industry leaders as they share the latest trends in digital marketing, social media strategies, and content creation.',
                'startDate' => new \DateTime('+2 weeks'),
                'endDate' => new \DateTime('+2 weeks +8 hours'),
                'location' => 'Grand Hotel, New York',
                'maxAttendees' => 200,
            ],
            [
                'title' => 'AI and Machine Learning Conference',
                'description' => 'Explore the future of artificial intelligence and machine learning. Network with experts and discover cutting-edge technologies.',
                'startDate' => new \DateTime('+1 month'),
                'endDate' => new \DateTime('+1 month +2 days'),
                'location' => 'Innovation Center, Austin',
                'maxAttendees' => 150,
            ],
            [
                'title' => 'DevOps Best Practices Meetup',
                'description' => 'A local meetup discussing DevOps practices, containerization, CI/CD pipelines, and infrastructure as code.',
                'startDate' => new \DateTime('+3 days'),
                'endDate' => new \DateTime('+3 days +4 hours'),
                'location' => 'Community Space, Seattle',
                'maxAttendees' => 30,
            ],
            [
                'title' => 'UX/UI Design Workshop',
                'description' => 'Learn user experience and interface design principles. Practical exercises in wireframing, prototyping, and user testing.',
                'startDate' => new \DateTime('+10 days'),
                'endDate' => new \DateTime('+10 days +5 hours'),
                'location' => 'Design Studio, Los Angeles',
                'maxAttendees' => 25,
            ],
        ];

        foreach ($sampleEvents as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setStartDate($eventData['startDate']);
            $event->setEndDate($eventData['endDate']);
            $event->setLocation($eventData['location']);
            $event->setMaxAttendees($eventData['maxAttendees']);
            
            // Generate slug
            $slug = $this->slugger->slug($eventData['title'])->lower();
            $event->setSlug($slug);
            
            $event->addAdministrator($admin);

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Created %d sample events successfully!', count($sampleEvents)));
        $io->note('You can now browse events at /events or access the admin panel to manage them.');

        return Command::SUCCESS;
    }
}