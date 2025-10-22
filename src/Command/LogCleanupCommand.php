<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:log-cleanup',
    description: 'Clean up old application log files'
)]
class LogCleanupCommand extends Command
{
    public function __construct(
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to keep logs', 30)
            ->addOption('security-days', 's', InputOption::VALUE_OPTIONAL, 'Number of days to keep security/audit logs', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
            ->setHelp('This command cleans up old log files to prevent disk space issues.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logDir = $this->projectDir . '/var/log';
        $daysToKeep = (int) $input->getOption('days');
        $securityDaysToKeep = (int) $input->getOption('security-days');
        $dryRun = $input->getOption('dry-run');

        if (!is_dir($logDir)) {
            $io->error("Log directory not found: $logDir");
            return Command::FAILURE;
        }

        $io->title('Application Log Cleanup');
        
        if ($dryRun) {
            $io->note('DRY RUN MODE - No files will actually be deleted');
        }

        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$daysToKeep}D"));
        
        $securityCutoffDate = new \DateTime();
        $securityCutoffDate->sub(new \DateInterval("P{$securityDaysToKeep}D"));

        $io->text("Cleaning logs older than {$daysToKeep} days (before {$cutoffDate->format('Y-m-d')})");
        $io->text("Cleaning security/audit logs older than {$securityDaysToKeep} days (before {$securityCutoffDate->format('Y-m-d')})");
        $io->newLine();

        $totalSize = 0;
        $deletedCount = 0;

        // Clean regular log files
        $finder = new Finder();
        $finder->files()
            ->in($logDir)
            ->name('*.log')
            ->notName('security.log')
            ->notName('audit.log')
            ->date("< {$cutoffDate->format('Y-m-d')}");

        foreach ($finder as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $deletedCount++;
            
            $io->text("Regular log: {$file->getFilename()} ({$this->formatBytes($size)}) - {$file->getMTime()->format('Y-m-d H:i:s')}");
            
            if (!$dryRun) {
                unlink($file->getPathname());
            }
        }

        // Clean security and audit logs (with longer retention)
        $securityFinder = new Finder();
        $securityFinder->files()
            ->in($logDir)
            ->name(['security.log*', 'audit.log*'])
            ->date("< {$securityCutoffDate->format('Y-m-d')}");

        foreach ($securityFinder as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $deletedCount++;
            
            $io->text("Security/Audit log: {$file->getFilename()} ({$this->formatBytes($size)}) - {$file->getMTime()->format('Y-m-d H:i:s')}");
            
            if (!$dryRun) {
                unlink($file->getPathname());
            }
        }

        // Clean compressed log files
        $compressedFinder = new Finder();
        $compressedFinder->files()
            ->in($logDir)
            ->name('*.gz')
            ->date("< {$cutoffDate->format('Y-m-d')}");

        foreach ($compressedFinder as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $deletedCount++;
            
            $io->text("Compressed log: {$file->getFilename()} ({$this->formatBytes($size)}) - {$file->getMTime()->format('Y-m-d H:i:s')}");
            
            if (!$dryRun) {
                unlink($file->getPathname());
            }
        }

        $io->newLine();
        
        if ($deletedCount > 0) {
            $action = $dryRun ? 'Would delete' : 'Deleted';
            $io->success("{$action} {$deletedCount} log files, freeing {$this->formatBytes($totalSize)} of disk space.");
        } else {
            $io->info('No old log files found to clean up.');
        }

        // Show current log directory size
        $this->showLogDirectoryInfo($io, $logDir);

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function showLogDirectoryInfo(SymfonyStyle $io, string $logDir): void
    {
        $finder = new Finder();
        $finder->files()->in($logDir);

        $totalSize = 0;
        $fileCount = 0;

        foreach ($finder as $file) {
            $totalSize += $file->getSize();
            $fileCount++;
        }

        $io->section('Current Log Directory Status');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Files', $fileCount],
                ['Total Size', $this->formatBytes($totalSize)],
                ['Directory', $logDir],
            ]
        );
    }
}