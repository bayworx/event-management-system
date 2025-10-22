<?php

namespace App\Entity;

use App\Repository\FeaturedEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: FeaturedEventRepository::class)]
#[ORM\Table(name: 'featured_events')]
#[Vich\Uploadable]
class FeaturedEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[Vich\UploadableField(mapping: 'featured_event_banners', fileNameProperty: 'bannerImageName', size: 'bannerImageSize')]
    private ?File $bannerImageFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $bannerImageName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bannerImageSize = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $linkUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $linkText = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $relatedEvent = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private int $priority = 50;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 50, options: ['default' => 'banner'])]
    #[Assert\Choice(choices: ['banner', 'card', 'popup', 'sidebar'])]
    private string $displayType = 'banner';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $displaySettings = [];

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $viewCount = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $clickCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Administrator $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->displaySettings = [
            'autoRotate' => true,
            'rotationInterval' => 5000, // 5 seconds
            'showControls' => true,
            'showIndicators' => true,
            'fadeEffect' => true
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl): static
    {
        $this->linkUrl = $linkUrl;
        return $this;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    public function setLinkText(?string $linkText): static
    {
        $this->linkText = $linkText;
        return $this;
    }

    public function getRelatedEvent(): ?Event
    {
        return $this->relatedEvent;
    }

    public function setRelatedEvent(?Event $relatedEvent): static
    {
        $this->relatedEvent = $relatedEvent;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDisplayType(): string
    {
        return $this->displayType;
    }

    public function setDisplayType(string $displayType): static
    {
        $this->displayType = $displayType;
        return $this;
    }

    public function getDisplaySettings(): ?array
    {
        return $this->displaySettings;
    }

    public function setDisplaySettings(?array $displaySettings): static
    {
        $this->displaySettings = $displaySettings;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
        return $this;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function setClickCount(int $clickCount): static
    {
        $this->clickCount = $clickCount;
        return $this;
    }

    public function incrementClickCount(): static
    {
        $this->clickCount++;
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

    public function setBannerImageFile(?File $bannerImageFile = null): void
    {
        $this->bannerImageFile = $bannerImageFile;

        if (null !== $bannerImageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getBannerImageFile(): ?File
    {
        return $this->bannerImageFile;
    }

    public function setBannerImageName(?string $bannerImageName): void
    {
        $this->bannerImageName = $bannerImageName;
    }

    public function getBannerImageName(): ?string
    {
        return $this->bannerImageName;
    }

    public function setBannerImageSize(?int $bannerImageSize): void
    {
        $this->bannerImageSize = $bannerImageSize;
    }

    public function getBannerImageSize(): ?int
    {
        return $this->bannerImageSize;
    }

    public function getCreatedBy(): ?Administrator
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Administrator $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * Check if the featured event is currently within its display period
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTime();

        if ($this->startDate && $now < $this->startDate) {
            return false;
        }

        if ($this->endDate && $now > $this->endDate) {
            return false;
        }

        return true;
    }

    /**
     * Get the effective link URL (either custom URL or related event URL)
     */
    public function getEffectiveLinkUrl(): ?string
    {
        if ($this->linkUrl) {
            return $this->linkUrl;
        }

        if ($this->relatedEvent) {
            // Generate correct route for individual event (singular "event")
            return "/event/{$this->relatedEvent->getSlug()}";
        }

        return null;
    }

    /**
     * Get the effective link text
     */
    public function getEffectiveLinkText(): ?string
    {
        if ($this->linkText) {
            return $this->linkText;
        }

        if ($this->relatedEvent) {
            return 'View Event';
        }

        return 'Learn More';
    }

    /**
     * Get click-through rate as percentage
     */
    public function getClickThroughRate(): float
    {
        if ($this->viewCount === 0) {
            return 0;
        }

        return round(($this->clickCount / $this->viewCount) * 100, 2);
    }

    /**
     * Get the effective banner image (uploaded banner first, fallback to imageUrl)
     */
    public function getEffectiveBannerImage(): ?string
    {
        if ($this->bannerImageName) {
            return '/uploads/featured_events/' . $this->bannerImageName;
        }

        return $this->imageUrl;
    }
}