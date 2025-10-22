<?php

namespace App\Entity;

use App\Repository\AppConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AppConfigRepository::class)]
#[ORM\Table(name: 'app_config')]
#[UniqueEntity(fields: ['configKey'], message: 'This configuration key already exists')]
class AppConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $configKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $configValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, options: ['default' => 'string'])]
    private string $valueType = 'string';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRequired = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }

    public function setConfigKey(string $configKey): static
    {
        $this->configKey = $configKey;
        return $this;
    }

    public function getConfigValue(): ?string
    {
        return $this->configValue;
    }

    public function setConfigValue(?string $configValue): static
    {
        $this->configValue = $configValue;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): static
    {
        $this->valueType = $valueType;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get the typed value based on valueType
     */
    public function getTypedValue(): mixed
    {
        if ($this->configValue === null) {
            return null;
        }

        return match ($this->valueType) {
            'boolean' => filter_var($this->configValue, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->configValue,
            'float' => (float) $this->configValue,
            'array', 'json' => json_decode($this->configValue, true),
            default => $this->configValue,
        };
    }

    /**
     * Set value with automatic type conversion
     */
    public function setTypedValue(mixed $value): static
    {
        if ($value === null) {
            $this->configValue = null;
            return $this;
        }

        $this->configValue = match ($this->valueType) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'float' => (string) (float) $value,
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };

        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function __toString(): string
    {
        return $this->configKey ?? '';
    }
}