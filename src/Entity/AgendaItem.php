<?php

namespace App\Entity;

use App\Repository\AgendaItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AgendaItemRepository::class)]
#[ORM\Table(name: 'agenda_items')]
class AgendaItem
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['session', 'break', 'lunch', 'keynote', 'workshop', 'networking', 'other'])]
    private ?string $itemType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $speaker = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isVisible = true;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'agendaItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Presenter::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Presenter $presenter = null;

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

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getSpeaker(): ?string
    {
        return $this->speaker;
    }

    public function setSpeaker(?string $speaker): static
    {
        $this->speaker = $speaker;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getPresenter(): ?Presenter
    {
        return $this->presenter;
    }

    public function setPresenter(?Presenter $presenter): static
    {
        $this->presenter = $presenter;
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
     * Get the duration in minutes
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->endTime || !$this->startTime) {
            return null;
        }

        return (int) $this->startTime->diff($this->endTime)->format('%i') + 
               (int) $this->startTime->diff($this->endTime)->format('%h') * 60;
    }

    /**
     * Get formatted time range
     */
    public function getTimeRange(): string
    {
        if (!$this->startTime) {
            return '';
        }

        $start = $this->startTime->format('H:i');
        
        if ($this->endTime) {
            $end = $this->endTime->format('H:i');
            return "$start - $end";
        }

        return $start;
    }

    /**
     * Get item type display label
     */
    public function getItemTypeLabel(): string
    {
        return match($this->itemType) {
            'session' => 'Session',
            'break' => 'Break',
            'lunch' => 'Lunch',
            'keynote' => 'Keynote',
            'workshop' => 'Workshop',
            'networking' => 'Networking',
            'other' => 'Other',
            default => ucfirst($this->itemType ?? '')
        };
    }

    /**
     * Get icon class for item type
     */
    public function getItemTypeIcon(): string
    {
        return match($this->itemType) {
            'session' => 'bi-chat-square-text',
            'break' => 'bi-cup-hot',
            'lunch' => 'bi-egg-fried',
            'keynote' => 'bi-megaphone',
            'workshop' => 'bi-tools',
            'networking' => 'bi-people',
            'other' => 'bi-calendar-event',
            default => 'bi-calendar-event'
        };
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}