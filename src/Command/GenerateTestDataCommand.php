<?php

namespace App\Command;

use App\Entity\Administrator;
use App\Entity\Event;
use App\Entity\Attendee;
use App\Entity\AgendaItem;
use App\Entity\Presenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generate test data for the application',
)]
class GenerateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Generating Test Data');

        // Create test administrator if not exists
        $adminRepo = $this->entityManager->getRepository(Administrator::class);
        $admin = $adminRepo->findOneBy(['email' => 'admin@example.com']);

        if (!$admin) {
            $admin = new Administrator();
            $admin->setEmail('admin@example.com');
            $admin->setFirstName('Test');
            $admin->setLastName('Admin');
            $admin->setIsSuperAdmin(true);
            
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'password123');
            $admin->setPassword($hashedPassword);

            $this->entityManager->persist($admin);
            $io->success('Created test administrator: admin@example.com (password: password123)');
        } else {
            $io->note('Test administrator already exists');
        }

        // Create test event if not exists
        $eventRepo = $this->entityManager->getRepository(Event::class);
        $event = $eventRepo->findOneBy(['title' => 'Sample Tech Conference']);

        if (!$event) {
            $event = new Event();
            $event->setTitle('Sample Tech Conference');
            $event->setSlug('sample-tech-conference');
            $event->setDescription('A sample technology conference for testing the import functionality');
            $event->setStartDate(new \DateTime('2024-12-15 09:00:00'));
            $event->setEndDate(new \DateTime('2024-12-15 17:00:00'));
            $event->setLocation('Convention Center');
            $event->setMaxAttendees(500);
            // Event entity doesn't have registrationDeadline or createdBy fields

            $this->entityManager->persist($event);

            // Create some attendees
            $attendeesData = [
                ['John Doe', 'john.doe@example.com', '555-123-4567', 'Tech Corp'],
                ['Mary Johnson', 'mary.johnson@example.com', '555-987-6543', 'Digital Solutions'],
                ['David Brown', 'david.brown@example.com', '555-456-7890', 'StartupXYZ'],
            ];

            foreach ($attendeesData as $attendeeData) {
                $attendee = new Attendee();
                $attendee->setName($attendeeData[0]);
                $attendee->setEmail($attendeeData[1]);
                $attendee->setPhone($attendeeData[2]);
                $attendee->setOrganization($attendeeData[3]);
                $attendee->setEvent($event);

                $this->entityManager->persist($attendee);
            }

            // Create agenda items
            $agendaData = [
                ['Opening Keynote', 'Welcome and industry overview', '09:00:00', '10:00:00', 'Main Hall', 'keynote'],
                ['Cloud Computing Workshop', 'Hands-on workshop on cloud technologies', '10:30:00', '12:00:00', 'Workshop Room A', 'workshop'],
                ['Networking Lunch', 'Networking opportunity with peers', '12:00:00', '13:30:00', 'Dining Hall', 'lunch'],
                ['AI Panel Discussion', 'Expert discussion on AI trends', '14:00:00', '15:30:00', 'Conference Room B', 'session'],
                ['Closing Remarks', 'Wrap-up and next steps', '16:00:00', '17:00:00', 'Main Hall', 'session'],
            ];

            foreach ($agendaData as $agendaItemData) {
                $agendaItem = new AgendaItem();
                $agendaItem->setTitle($agendaItemData[0]);
                $agendaItem->setDescription($agendaItemData[1]);
                $agendaItem->setStartTime(new \DateTime('2024-12-15 ' . $agendaItemData[2]));
                $agendaItem->setEndTime(new \DateTime('2024-12-15 ' . $agendaItemData[3]));
                $agendaItem->setLocation($agendaItemData[4]);
                $agendaItem->setItemType($agendaItemData[5]);
                $agendaItem->setEvent($event);

                // Create presenters for some agenda items
                if ($agendaItemData[0] === 'Opening Keynote') {
                    $presenter = new Presenter();
                    $presenter->setName('Jane Smith');
                    $presenter->setEmail('jane.smith@example.com');
                    $presenter->setBio('Technology evangelist with 15 years experience');
                    $presenter->setTitle('CTO');
                    $presenter->setCompany('Innovation Labs');
                    $this->entityManager->persist($presenter);
                    
                    $agendaItem->setPresenter($presenter);
                } elseif ($agendaItemData[0] === 'Cloud Computing Workshop') {
                    $presenter = new Presenter();
                    $presenter->setName('Bob Wilson');
                    $presenter->setEmail('bob.wilson@example.com');
                    $presenter->setBio('Cloud architect and consultant');
                    $presenter->setTitle('Senior Architect');
                    $presenter->setCompany('Cloud Systems Inc');
                    $this->entityManager->persist($presenter);
                    
                    $agendaItem->setPresenter($presenter);
                }

                $this->entityManager->persist($agendaItem);
            }

            $io->success('Created test event with attendees, agenda items, and presenters');
        } else {
            $io->note('Test event already exists');
        }

        $this->entityManager->flush();

        $io->success('Test data generation completed!');
        
        $io->section('Available Test Accounts');
        $io->table(
            ['Email', 'Password', 'Role'],
            [['admin@example.com', 'password123', 'Super Admin']]
        );

        return Command::SUCCESS;
    }
}