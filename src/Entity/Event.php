<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[Vich\Uploadable]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxAttendees = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bannerImage = null;

    #[Vich\UploadableField(mapping: 'event_banners', fileNameProperty: 'bannerImage')]
    private ?File $bannerFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Attendee::class, cascade: ['persist', 'remove'])]
    private Collection $attendees;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventFile::class, cascade: ['persist', 'remove'])]
    private Collection $files;

    #[ORM\ManyToMany(targetEntity: Administrator::class, inversedBy: 'managedEvents')]
    #[ORM\JoinTable(name: 'event_administrators')]
    private Collection $administrators;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventPresenter::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $eventPresenters;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: AgendaItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startTime' => 'ASC', 'sortOrder' => 'ASC'])]
    private Collection $agendaItems;

    public function __construct()
    {
        $this->attendees = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->administrators = new ArrayCollection();
        $this->eventPresenters = new ArrayCollection();
        $this->agendaItems = new ArrayCollection();
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getMaxAttendees(): ?int
    {
        return $this->maxAttendees;
    }

    public function setMaxAttendees(?int $maxAttendees): static
    {
        $this->maxAttendees = $maxAttendees;
        return $this;
    }

    public function getBannerImage(): ?string
    {
        return $this->bannerImage;
    }

    public function setBannerImage(?string $bannerImage): static
    {
        $this->bannerImage = $bannerImage;
        return $this;
    }

    public function getBannerFile(): ?File
    {
        return $this->bannerFile;
    }

    public function setBannerFile(?File $bannerFile = null): static
    {
        $this->bannerFile = $bannerFile;
        
        if (null !== $bannerFile) {
            $this->updatedAt = new \DateTime();
        }
        
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
     * @return Collection<int, Attendee>
     */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(Attendee $attendee): static
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees->add($attendee);
            $attendee->setEvent($this);
        }
        return $this;
    }

    public function removeAttendee(Attendee $attendee): static
    {
        if ($this->attendees->removeElement($attendee)) {
            if ($attendee->getEvent() === $this) {
                $attendee->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, EventFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(EventFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setEvent($this);
        }
        return $this;
    }

    public function removeFile(EventFile $file): static
    {
        if ($this->files->removeElement($file)) {
            if ($file->getEvent() === $this) {
                $file->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Administrator>
     */
    public function getAdministrators(): Collection
    {
        return $this->administrators;
    }

    public function addAdministrator(Administrator $administrator): static
    {
        if (!$this->administrators->contains($administrator)) {
            $this->administrators->add($administrator);
        }
        return $this;
    }

    public function removeAdministrator(Administrator $administrator): static
    {
        $this->administrators->removeElement($administrator);
        return $this;
    }

    public function getAttendeesCount(): int
    {
        return $this->attendees->count();
    }

    public function canAcceptMoreAttendees(): bool
    {
        if (!$this->maxAttendees) {
            return true;
        }
        return $this->getAttendeesCount() < $this->maxAttendees;
    }

    /**
     * @return Collection<int, EventPresenter>
     */
    public function getEventPresenters(): Collection
    {
        return $this->eventPresenters;
    }

    public function addEventPresenter(EventPresenter $eventPresenter): static
    {
        if (!$this->eventPresenters->contains($eventPresenter)) {
            $this->eventPresenters->add($eventPresenter);
            $eventPresenter->setEvent($this);
        }
        return $this;
    }

    public function removeEventPresenter(EventPresenter $eventPresenter): static
    {
        if ($this->eventPresenters->removeElement($eventPresenter)) {
            if ($eventPresenter->getEvent() === $this) {
                $eventPresenter->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * Get all presenters for this event
     * @return Collection<int, Presenter>
     */
    public function getPresenters(): Collection
    {
        return $this->eventPresenters->map(fn(EventPresenter $ep) => $ep->getPresenter());
    }

    /**
     * Get visible presenters ordered by sort order
     * @return Collection<int, EventPresenter>
     */
    public function getVisiblePresenters(): Collection
    {
        return $this->eventPresenters->filter(fn(EventPresenter $ep) => $ep->isVisible());
    }

    /**
     * @return Collection<int, AgendaItem>
     */
    public function getAgendaItems(): Collection
    {
        return $this->agendaItems;
    }

    public function addAgendaItem(AgendaItem $agendaItem): static
    {
        if (!$this->agendaItems->contains($agendaItem)) {
            $this->agendaItems->add($agendaItem);
            $agendaItem->setEvent($this);
        }
        return $this;
    }

    public function removeAgendaItem(AgendaItem $agendaItem): static
    {
        if ($this->agendaItems->removeElement($agendaItem)) {
            if ($agendaItem->getEvent() === $this) {
                $agendaItem->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * Get visible agenda items ordered by start time and sort order
     */
    public function getVisibleAgendaItems(): Collection
    {
        return $this->agendaItems->filter(fn(AgendaItem $item) => $item->isVisible());
    }

    /**
     * Get agenda items count
     */
    public function getAgendaItemsCount(): int
    {
        return $this->agendaItems->count();
    }

    /**
     * Check if event has agenda
     */
    public function hasAgenda(): bool
    {
        return $this->agendaItems->count() > 0;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
    
    /**
     * Auto-assign sort orders to presenters that don't have one
     */
    public function autoAssignPresenterSortOrders(): void
    {
        $maxOrder = 0;
        
        // First, find the current max sort order
        foreach ($this->eventPresenters as $eventPresenter) {
            if ($eventPresenter->getSortOrder() !== null && $eventPresenter->getSortOrder() > $maxOrder) {
                $maxOrder = $eventPresenter->getSortOrder();
            }
        }
        
        // Then assign sort orders to those without one
        foreach ($this->eventPresenters as $eventPresenter) {
            if ($eventPresenter->getSortOrder() === null) {
                $eventPresenter->setSortOrder(++$maxOrder);
            }
        }
    }

    public function __sleep(): array
    {
        // Exclude bannerFile from serialization to prevent UploadedFile serialization errors
        return ['id', 'title', 'description', 'startDate', 'endDate', 'location', 'maxAttendees', 
                'isActive', 'slug', 'bannerImage', 'createdAt', 'updatedAt', 'attendees', 
                'administrators', 'files', 'eventPresenters', 'agendaItems'];
    }
    
    public function __wakeup(): void
    {
        // Reset bannerFile to null after unserialization
        $this->bannerFile = null;
    }
}
