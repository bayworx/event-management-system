<?php

namespace App\Command;

use App\Service\ConfigurationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-config',
    description: 'Initialize application configuration with default values',
)]
class InitConfigCommand extends Command
{
    public function __construct(
        private ConfigurationService $configService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initializing Application Configuration');

        try {
            $this->configService->initializeDefaults();
            
            $io->success('Configuration initialized successfully!');
            
            // Display some key configuration values
            $companyInfo = $this->configService->getCompanyInfo();
            $appPrefs = $this->configService->getAppPreferences();
            
            $io->section('Current Configuration');
            $io->table([
                ['Setting', 'Value'],
                ['Company Name', $companyInfo['name']],
                ['Application Version', $appPrefs['version']],
                ['Timezone', $appPrefs['timezone']],
                ['Items Per Page', $appPrefs['items_per_page']],
                ['Theme', $appPrefs['theme']],
            ], []);
            
            $io->note('You can now access the configuration panel at /admin/config to customize these settings.');
            
        } catch (\Exception $e) {
            $io->error('Failed to initialize configuration: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}