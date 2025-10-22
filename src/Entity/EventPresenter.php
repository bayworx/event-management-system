<?php

namespace App\Entity;

use App\Repository\EventPresenterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventPresenterRepository::class)]
#[ORM\Table(name: 'event_presenter')]
class EventPresenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'eventPresenters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Presenter::class, inversedBy: 'eventPresenters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Presenter $presenter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $presentationTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $presentationDescription = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $sortOrder = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isVisible = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPresentationTitle(): ?string
    {
        return $this->presentationTitle;
    }

    public function setPresentationTitle(?string $presentationTitle): static
    {
        $this->presentationTitle = $presentationTitle;
        return $this;
    }

    public function getPresentationDescription(): ?string
    {
        return $this->presentationDescription;
    }

    public function setPresentationDescription(?string $presentationDescription): static
    {
        $this->presentationDescription = $presentationDescription;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
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

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDisplayTitle(): string
    {
        return $this->presentationTitle ?? $this->presenter?->getName() ?? 'Presentation';
    }

    public function getTimeRange(): ?string
    {
        if (!$this->startTime) {
            return null;
        }

        $start = $this->startTime->format('H:i');
        
        if ($this->endTime) {
            return $start . ' - ' . $this->endTime->format('H:i');
        }

        return $start;
    }

    public function __toString(): string
    {
        $presenter = $this->presenter ? $this->presenter->getName() : 'Unknown Presenter';
        $title = $this->presentationTitle ? ': ' . $this->presentationTitle : '';
        return $presenter . $title;
    }
}