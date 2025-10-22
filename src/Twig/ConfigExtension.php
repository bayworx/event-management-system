<?php

namespace App\Twig;

use App\Service\ConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    public function __construct(
        private ConfigurationService $configService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [$this, 'getConfig']),
            new TwigFunction('company_config', [$this, 'getCompanyConfig']),
            new TwigFunction('app_config', [$this, 'getAppConfig']),
            new TwigFunction('email_config', [$this, 'getEmailConfig']),
            new TwigFunction('footer_config', [$this, 'getFooterConfig']),
        ];
    }

    /**
     * Get a configuration value by key
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->configService->get($key, $default);
    }

    /**
     * Get all company configuration
     */
    public function getCompanyConfig(): array
    {
        return $this->configService->getCompanyInfo();
    }

    /**
     * Get all application configuration
     */
    public function getAppConfig(): array
    {
        return $this->configService->getAppPreferences();
    }

    /**
     * Get all email configuration
     */
    public function getEmailConfig(): array
    {
        return $this->configService->getEmailSettings();
    }

    /**
     * Get all footer configuration
     */
    public function getFooterConfig(): array
    {
        return $this->configService->getFooterSettings();
    }
}