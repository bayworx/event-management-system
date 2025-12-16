<?php

namespace App\Command;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\ApplicationLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deactivate-past-events',
    description: 'Automatically deactivate events that have ended'
)]
class DeactivatePastEventsCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager,
        private ApplicationLogger $appLogger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deactivated without actually deactivating')
            ->addOption('grace-period', 'g', InputOption::VALUE_OPTIONAL, 'Hours after event end to wait before deactivating', 0)
            ->setHelp(
                'This command deactivates events that have ended based on their end date (or start date if no end date).' . PHP_EOL .
                'It only affects currently active events.' . PHP_EOL . PHP_EOL .
                'Examples:' . PHP_EOL .
                '  php bin/console app:deactivate-past-events' . PHP_EOL .
                '  php bin/console app:deactivate-past-events --dry-run' . PHP_EOL .
                '  php bin/console app:deactivate-past-events --grace-period=24' . PHP_EOL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $gracePeriodHours = (int) $input->getOption('grace-period');

        $io->title('Deactivate Past Events');
        
        if ($dryRun) {
            $io->note('DRY RUN MODE - No events will actually be deactivated');
        }

        if ($gracePeriodHours > 0) {
            $io->text("Using grace period of {$gracePeriodHours} hours after event end");
        }

        // Calculate the cutoff date
        $cutoffDate = new \DateTime();
        if ($gracePeriodHours > 0) {
            $cutoffDate->sub(new \DateInterval("PT{$gracePeriodHours}H"));
        }

        $io->text("Checking for events that ended before: " . $cutoffDate->format('Y-m-d H:i:s'));
        $io->newLine();

        // Find active events that have ended
        $qb = $this->eventRepository->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.endDate', 'ASC');

        // Build the date condition: use endDate if available, otherwise use startDate
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('e.endDate'),
                    $qb->expr()->lt('e.endDate', ':cutoffDate')
                ),
                $qb->expr()->andX(
                    $qb->expr()->isNull('e.endDate'),
                    $qb->expr()->lt('e.startDate', ':cutoffDate')
                )
            )
        )->setParameter('cutoffDate', $cutoffDate);

        $pastEvents = $qb->getQuery()->getResult();

        if (count($pastEvents) === 0) {
            $io->success('No past events found that need to be deactivated.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d event(s) to deactivate:', count($pastEvents)));

        $deactivatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($pastEvents as $event) {
            $eventDate = $event->getEndDate() ?? $event->getStartDate();
            $dateType = $event->getEndDate() ? 'end' : 'start';
            
            $eventInfo = sprintf(
                '  [%d] %s (slug: %s) - %s date: %s',
                $event->getId(),
                $event->getTitle(),
                $event->getSlug(),
                $dateType,
                $eventDate->format('Y-m-d H:i')
            );

            try {
                // Check if event is recurring instance
                if ($event->isRecurringInstance()) {
                    $io->text($eventInfo . ' <comment>[Recurring instance]</comment>');
                } else {
                    $io->text($eventInfo);
                }

                if (!$dryRun) {
                    $event->setIsActive(false);
                    $this->entityManager->persist($event);
                    
                    // Log the deactivation
                    $this->appLogger->logEventOperation(
                        'event_auto_deactivated',
                        $event->getId(),
                        null,
                        [
                            'event_title' => $event->getTitle(),
                            'event_slug' => $event->getSlug(),
                            'end_date' => $eventDate->format('Y-m-d H:i:s'),
                            'deactivation_reason' => 'past_event',
                            'grace_period_hours' => $gracePeriodHours
                        ]
                    );
                }
                
                $deactivatedCount++;
            } catch (\Exception $e) {
                $io->error("Failed to deactivate event ID {$event->getId()}: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Flush all changes at once
        if (!$dryRun && $deactivatedCount > 0) {
            try {
                $this->entityManager->flush();
                $io->newLine();
                $io->success(sprintf(
                    'Successfully deactivated %d event(s).',
                    $deactivatedCount
                ));
            } catch (\Exception $e) {
                $io->error('Failed to save changes: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->newLine();
            $action = $dryRun ? 'Would deactivate' : 'Deactivated';
            $io->success(sprintf(
                '%s %d event(s).',
                $action,
                $deactivatedCount
            ));
        }

        // Show summary
        if ($skippedCount > 0 || $errorCount > 0) {
            $io->section('Summary');
            $summaryData = [
                ['Metric', 'Count'],
                ['Deactivated', $deactivatedCount],
            ];
            
            if ($skippedCount > 0) {
                $summaryData[] = ['Skipped', $skippedCount];
            }
            
            if ($errorCount > 0) {
                $summaryData[] = ['Errors', $errorCount];
            }
            
            $io->table(['Metric', 'Count'], array_slice($summaryData, 1));
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
