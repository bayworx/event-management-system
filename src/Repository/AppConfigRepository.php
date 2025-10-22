<?php

namespace App\Repository;

use App\Entity\AppConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppConfig>
 *
 * @method AppConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppConfig[]    findAll()
 * @method AppConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppConfig::class);
    }

    /**
     * Find configuration by key
     */
    public function findByKey(string $key): ?AppConfig
    {
        return $this->findOneBy(['configKey' => $key]);
    }

    /**
     * Get configuration value by key
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $config = $this->findByKey($key);
        
        if (!$config) {
            return $default;
        }

        $value = $config->getTypedValue();
        return $value !== null ? $value : $default;
    }

    /**
     * Set configuration value by key
     */
    public function setConfigValue(string $key, mixed $value, string $category = null, string $description = null): AppConfig
    {
        $config = $this->findByKey($key);
        
        if (!$config) {
            $config = new AppConfig();
            $config->setConfigKey($key);
            if ($category) {
                $config->setCategory($category);
            }
            if ($description) {
                $config->setDescription($description);
            }
        }

        // Determine value type based on the value
        $valueType = match (gettype($value)) {
            'boolean' => 'boolean',
            'integer' => 'integer',
            'double' => 'float',
            'array' => 'json',
            default => 'string',
        };

        $config->setValueType($valueType);
        $config->setTypedValue($value);

        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();

        return $config;
    }

    /**
     * Find configurations by category
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], ['configKey' => 'ASC']);
    }

    /**
     * Get all categories
     */
    public function getAllCategories(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('DISTINCT c.category')
            ->where('c.category IS NOT NULL')
            ->orderBy('c.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'category');
    }

    /**
     * Get configurations grouped by category
     */
    public function getConfigsByCategory(): array
    {
        $configs = $this->findBy([], ['category' => 'ASC', 'configKey' => 'ASC']);
        $grouped = [];

        foreach ($configs as $config) {
            $category = $config->getCategory() ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $config;
        }

        return $grouped;
    }

    /**
     * Initialize default configurations
     */
    public function initializeDefaults(): void
    {
        $defaults = [
            // Company Information
            ['key' => 'company.name', 'value' => 'BAYWORX EMS', 'category' => 'Company', 'description' => 'Company name displayed throughout the application'],
            ['key' => 'company.description', 'value' => 'Professional Event Management System', 'category' => 'Company', 'description' => 'Company description or tagline'],
            ['key' => 'company.address', 'value' => '', 'category' => 'Company', 'description' => 'Company address'],
            ['key' => 'company.phone', 'value' => '', 'category' => 'Company', 'description' => 'Company phone number'],
            ['key' => 'company.email', 'value' => 'info@example.com', 'category' => 'Company', 'description' => 'Company contact email'],
            ['key' => 'company.website', 'value' => '', 'category' => 'Company', 'description' => 'Company website URL'],
            ['key' => 'company.logo', 'value' => '', 'category' => 'Company', 'description' => 'Company logo filename'],

            // Application Preferences
            ['key' => 'app.timezone', 'value' => 'UTC', 'category' => 'Application', 'description' => 'Default application timezone'],
            ['key' => 'app.date_format', 'value' => 'M j, Y', 'category' => 'Application', 'description' => 'Default date display format'],
            ['key' => 'app.time_format', 'value' => 'g:i A', 'category' => 'Application', 'description' => 'Default time display format'],
            ['key' => 'app.items_per_page', 'value' => '20', 'category' => 'Application', 'description' => 'Default number of items per page in lists', 'type' => 'integer'],
            ['key' => 'app.theme', 'value' => 'default', 'category' => 'Application', 'description' => 'Application theme'],
            ['key' => 'app.maintenance_mode', 'value' => '0', 'category' => 'Application', 'description' => 'Enable maintenance mode', 'type' => 'boolean'],
            ['key' => 'app.version', 'value' => '1.0.0', 'category' => 'Application', 'description' => 'Application version number'],

            // Email Settings
            ['key' => 'email.from_name', 'value' => 'BAYWORX EMS', 'category' => 'Email', 'description' => 'Default sender name for emails'],
            ['key' => 'email.from_email', 'value' => 'noreply@example.com', 'category' => 'Email', 'description' => 'Default sender email address'],
            ['key' => 'email.signature', 'value' => 'Best regards,<br>The BAYWORX EMS Team', 'category' => 'Email', 'description' => 'Default email signature'],

            // Event Settings
            ['key' => 'events.default_max_attendees', 'value' => '100', 'category' => 'Events', 'description' => 'Default maximum number of attendees for new events', 'type' => 'integer'],
            ['key' => 'events.enable_registration', 'value' => '1', 'category' => 'Events', 'description' => 'Enable event registration functionality', 'type' => 'boolean'],
            ['key' => 'events.require_approval', 'value' => '0', 'category' => 'Events', 'description' => 'Require admin approval for event registrations', 'type' => 'boolean'],
            ['key' => 'events.allow_cancellation', 'value' => '1', 'category' => 'Events', 'description' => 'Allow attendees to cancel their registration', 'type' => 'boolean'],

            // Footer Settings
            ['key' => 'footer.text', 'value' => '', 'category' => 'Footer', 'description' => 'Custom footer text or description'],
            ['key' => 'footer.show_company_info', 'value' => '1', 'category' => 'Footer', 'description' => 'Show company information in footer', 'type' => 'boolean'],
            ['key' => 'footer.show_version', 'value' => '1', 'category' => 'Footer', 'description' => 'Show application version in footer', 'type' => 'boolean'],
            ['key' => 'footer.copyright_text', 'value' => '', 'category' => 'Footer', 'description' => 'Custom copyright text (leave empty for auto-generated)'],
            ['key' => 'footer.link_1_text', 'value' => '', 'category' => 'Footer', 'description' => 'First footer link text'],
            ['key' => 'footer.link_1_url', 'value' => '', 'category' => 'Footer', 'description' => 'First footer link URL'],
            ['key' => 'footer.link_2_text', 'value' => '', 'category' => 'Footer', 'description' => 'Second footer link text'],
            ['key' => 'footer.link_2_url', 'value' => '', 'category' => 'Footer', 'description' => 'Second footer link URL'],
            ['key' => 'footer.link_3_text', 'value' => '', 'category' => 'Footer', 'description' => 'Third footer link text'],
            ['key' => 'footer.link_3_url', 'value' => '', 'category' => 'Footer', 'description' => 'Third footer link URL'],
            ['key' => 'footer.social_facebook', 'value' => '', 'category' => 'Footer', 'description' => 'Facebook URL'],
            ['key' => 'footer.social_twitter', 'value' => '', 'category' => 'Footer', 'description' => 'Twitter/X URL'],
            ['key' => 'footer.social_linkedin', 'value' => '', 'category' => 'Footer', 'description' => 'LinkedIn URL'],
            ['key' => 'footer.social_instagram', 'value' => '', 'category' => 'Footer', 'description' => 'Instagram URL'],
        ];

        foreach ($defaults as $default) {
            $existing = $this->findByKey($default['key']);
            if (!$existing) {
                $config = new AppConfig();
                $config->setConfigKey($default['key']);
                $config->setCategory($default['category']);
                $config->setDescription($default['description']);
                
                if (isset($default['type'])) {
                    $config->setValueType($default['type']);
                } else {
                    // Determine type from value
                    if (is_bool($default['value']) || in_array($default['value'], ['0', '1'])) {
                        $config->setValueType('boolean');
                    } elseif (is_numeric($default['value'])) {
                        $config->setValueType('integer');
                    } else {
                        $config->setValueType('string');
                    }
                }
                
                $config->setTypedValue($default['value']);
                
                $this->getEntityManager()->persist($config);
            }
        }

        $this->getEntityManager()->flush();
    }
}