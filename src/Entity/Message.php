<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\ManyToOne(targetEntity: Attendee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Attendee $sender = null;

    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Administrator $recipient = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $readAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $replyTo = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'sent';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $priority = 'normal';

    public function __construct()
    {
        $this->sentAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getSender(): ?Attendee
    {
        return $this->sender;
    }

    public function setSender(?Attendee $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getRecipient(): ?Administrator
    {
        return $this->recipient;
    }

    public function setRecipient(?Administrator $recipient): static
    {
        $this->recipient = $recipient;
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

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTime();
        }
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getReplyTo(): ?self
    {
        return $this->replyTo;
    }

    public function setReplyTo(?self $replyTo): static
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function isReply(): bool
    {
        return $this->replyTo !== null;
    }

    public function markAsRead(): void
    {
        $this->setIsRead(true);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'sent' => 'Sent',
            'read' => 'Read',
            'replied' => 'Replied',
            'archived' => 'Archived',
            default => 'Unknown'
        };
    }

    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Normal'
        };
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'low' => 'secondary',
            'normal' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'primary'
        };
    }

    public function getShortContent(int $length = 100): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }
        
        return substr($this->content, 0, $length) . '...';
    }

    public function __toString(): string
    {
        return $this->subject ?? '';
    }
}