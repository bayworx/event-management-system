<?php

namespace App\Service;

use App\Repository\AppConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class ConfigurationService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'app_config_';

    public function __construct(
        private AppConfigRepository $configRepository,
        private EntityManagerInterface $entityManager,
        private ?CacheItemPoolInterface $cache = null
    ) {
    }

    /**
     * Get configuration value by key with caching
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Try cache first
        if ($this->cache) {
            $cacheKey = self::CACHE_PREFIX . str_replace('.', '_', $key);
            $cacheItem = $this->cache->getItem($cacheKey);
            
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        }

        $value = $this->configRepository->getConfigValue($key, $default);

        // Store in cache
        if ($this->cache && isset($cacheItem)) {
            $cacheItem->set($value);
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);
        }

        return $value;
    }

    /**
     * Set configuration value by key and clear cache
     */
    public function set(string $key, mixed $value, string $category = null, string $description = null): void
    {
        $this->configRepository->setConfigValue($key, $value, $category, $description);
        $this->clearCache($key);
    }

    /**
     * Get multiple configuration values by keys
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Get all configurations grouped by category
     */
    public function getByCategory(string $category): array
    {
        $configs = $this->configRepository->findByCategory($category);
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->getConfigKey()] = $config->getTypedValue();
        }
        
        return $result;
    }

    /**
     * Get all configurations grouped by categories
     */
    public function getAllGrouped(): array
    {
        return $this->configRepository->getConfigsByCategory();
    }

    /**
     * Get company information
     */
    public function getCompanyInfo(): array
    {
        return [
            'name' => $this->get('company.name', 'BAYWORX EMS'),
            'description' => $this->get('company.description', 'Professional Event Management System'),
            'address' => $this->get('company.address'),
            'phone' => $this->get('company.phone'),
            'email' => $this->get('company.email', 'info@example.com'),
            'website' => $this->get('company.website'),
            'logo' => $this->get('company.logo'),
        ];
    }

    /**
     * Get application preferences
     */
    public function getAppPreferences(): array
    {
        return [
            'timezone' => $this->get('app.timezone', 'UTC'),
            'date_format' => $this->get('app.date_format', 'M j, Y'),
            'time_format' => $this->get('app.time_format', 'g:i A'),
            'items_per_page' => (int) $this->get('app.items_per_page', 20),
            'theme' => $this->get('app.theme', 'default'),
            'maintenance_mode' => (bool) $this->get('app.maintenance_mode', false),
            'version' => $this->get('app.version', '1.0.0'),
        ];
    }

    /**
     * Get email settings
     */
    public function getEmailSettings(): array
    {
        return [
            'from_name' => $this->get('email.from_name', 'BAYWORX EMS'),
            'from_email' => $this->get('email.from_email', 'noreply@example.com'),
            'signature' => $this->get('email.signature', 'Best regards,<br>The BAYWORX EMS Team'),
        ];
    }

    /**
     * Get event settings
     */
    public function getEventSettings(): array
    {
        return [
            'default_max_attendees' => $this->get('events.default_max_attendees', 100),
            'enable_registration' => $this->get('events.enable_registration', true),
            'require_approval' => $this->get('events.require_approval', false),
            'allow_cancellation' => $this->get('events.allow_cancellation', true),
        ];
    }

    /**
     * Get footer settings
     */
    public function getFooterSettings(): array
    {
        return [
            'text' => $this->get('footer.text', ''),
            'show_company_info' => (bool) $this->get('footer.show_company_info', true),
            'show_version' => (bool) $this->get('footer.show_version', true),
            'copyright_text' => $this->get('footer.copyright_text', ''),
            'link_1_text' => $this->get('footer.link_1_text', ''),
            'link_1_url' => $this->get('footer.link_1_url', ''),
            'link_2_text' => $this->get('footer.link_2_text', ''),
            'link_2_url' => $this->get('footer.link_2_url', ''),
            'link_3_text' => $this->get('footer.link_3_text', ''),
            'link_3_url' => $this->get('footer.link_3_url', ''),
            'social_facebook' => $this->get('footer.social_facebook', ''),
            'social_twitter' => $this->get('footer.social_twitter', ''),
            'social_linkedin' => $this->get('footer.social_linkedin', ''),
            'social_instagram' => $this->get('footer.social_instagram', ''),
        ];
    }

    /**
     * Update multiple configurations at once
     */
    public function updateMultiple(array $configurations): void
    {
        foreach ($configurations as $key => $value) {
            // Convert boolean values to proper string representation
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $this->configRepository->setConfigValue($key, $value);
            $this->clearCache($key);
        }
    }

    /**
     * Initialize default configurations
     */
    public function initializeDefaults(): void
    {
        $this->configRepository->initializeDefaults();
        $this->clearAllCache();
    }

    /**
     * Check if configuration exists
     */
    public function has(string $key): bool
    {
        return $this->configRepository->findByKey($key) !== null;
    }

    /**
     * Clear cache for specific key
     */
    public function clearCache(string $key): void
    {
        if ($this->cache) {
            $cacheKey = self::CACHE_PREFIX . str_replace('.', '_', $key);
            $this->cache->deleteItem($cacheKey);
        }
    }

    /**
     * Clear all configuration cache
     */
    public function clearAllCache(): void
    {
        if ($this->cache) {
            $this->cache->clear();
        }
    }

    /**
     * Get configuration for Twig templates
     */
    public function getForTwig(): array
    {
        return [
            'company' => $this->getCompanyInfo(),
            'app' => $this->getAppPreferences(),
            'email' => $this->getEmailSettings(),
            'events' => $this->getEventSettings(),
            'footer' => $this->getFooterSettings(),
        ];
    }

    /**
     * Export all configurations
     */
    public function export(): array
    {
        $configs = $this->configRepository->findAll();
        $export = [];

        foreach ($configs as $config) {
            $export[] = [
                'key' => $config->getConfigKey(),
                'value' => $config->getConfigValue(),
                'category' => $config->getCategory(),
                'description' => $config->getDescription(),
                'type' => $config->getValueType(),
                'required' => $config->isRequired(),
            ];
        }

        return $export;
    }
}