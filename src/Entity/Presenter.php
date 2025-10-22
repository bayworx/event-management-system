<?php

namespace App\Entity;

use App\Repository\PresenterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PresenterRepository::class)]
#[Vich\Uploadable]
class Presenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twitter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[Vich\UploadableField(mapping: 'presenter_photos', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'presenter', targetEntity: EventPresenter::class, cascade: ['persist', 'remove'])]
    private Collection $eventPresenters;

    public function __construct()
    {
        $this->eventPresenters = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;
        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): static
    {
        $this->twitter = $twitter;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile = null): static
    {
        $this->photoFile = $photoFile;
        
        if (null !== $photoFile) {
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
            $eventPresenter->setPresenter($this);
        }

        return $this;
    }

    public function removeEventPresenter(EventPresenter $eventPresenter): static
    {
        if ($this->eventPresenters->removeElement($eventPresenter)) {
            if ($eventPresenter->getPresenter() === $this) {
                $eventPresenter->setPresenter(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        $parts = [];
        if ($this->name) {
            $parts[] = $this->name;
        }
        if ($this->title && $this->company) {
            $parts[] = $this->title . ' at ' . $this->company;
        } elseif ($this->title) {
            $parts[] = $this->title;
        } elseif ($this->company) {
            $parts[] = $this->company;
        }

        return implode(' - ', $parts);
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function __sleep(): array
    {
        // Exclude photoFile from serialization to prevent UploadedFile serialization errors
        return ['id', 'name', 'email', 'title', 'company', 'bio', 'website', 
                'linkedin', 'twitter', 'photo', 'createdAt', 'updatedAt', 'eventPresenters'];
    }
    
    public function __wakeup(): void
    {
        // Reset photoFile to null after unserialization
        $this->photoFile = null;
    }
}