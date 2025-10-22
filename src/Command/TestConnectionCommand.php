<?php

namespace App\Command;

use App\Repository\EventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-connection',
    description: 'Test database connection and basic functionality',
)]
class TestConnectionCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->note('Testing database connection...');
            
            // Test basic repository functionality
            $events = $this->eventRepository->findAll();
            $io->success('Database connection successful!');
            $io->info(sprintf('Found %d events in database', count($events)));
            
            // Test statistics method
            $stats = $this->eventRepository->getStatistics();
            $io->info(sprintf('Event statistics: Total=%d, Upcoming=%d, Past=%d', 
                $stats['total'], $stats['upcoming'], $stats['past']));
            
            foreach ($events as $event) {
                $io->writeln(sprintf('- %s (Slug: %s)', $event->getTitle(), $event->getSlug()));
            }
            
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            $io->note('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}