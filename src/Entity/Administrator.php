<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
#[ORM\Table(name: 'administrators')]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered')]
class Administrator implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_ADMIN'];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'administrators')]
    private Collection $managedEvents;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isSuperAdmin = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $department = null;

    public function __construct()
    {
        $this->managedEvents = new ArrayCollection();
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

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        
        // guarantee every admin at least has ROLE_ADMIN
        $roles[] = 'ROLE_ADMIN';
        
        // Super admins get additional privileges
        if ($this->isSuperAdmin) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getManagedEvents(): Collection
    {
        return $this->managedEvents;
    }

    public function addManagedEvent(Event $managedEvent): static
    {
        if (!$this->managedEvents->contains($managedEvent)) {
            $this->managedEvents->add($managedEvent);
            $managedEvent->addAdministrator($this);
        }

        return $this;
    }

    public function removeManagedEvent(Event $managedEvent): static
    {
        if ($this->managedEvents->removeElement($managedEvent)) {
            $managedEvent->removeAdministrator($this);
        }

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    public function setIsSuperAdmin(bool $isSuperAdmin): static
    {
        $this->isSuperAdmin = $isSuperAdmin;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    public function canManageEvent(Event $event): bool
    {
        return $this->isSuperAdmin || $this->managedEvents->contains($event);
    }

    public function updateLastLogin(): void
    {
        $this->lastLoginAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->name ?? $this->email ?? '';
    }
}