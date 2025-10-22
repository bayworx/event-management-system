<?php

namespace App\Entity;

use App\Repository\EventImportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventImportRepository::class)]
#[ORM\Table(name: 'event_imports')]
class EventImport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 50)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $importType = 'complete'; // complete, events_only, attendees_only, agenda_only, presenters_only

    #[ORM\ManyToOne(targetEntity: Administrator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Administrator $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $results = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $errors = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $totalRows = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $successfulRows = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $failedRows = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $importedData = null; // Store preview data before processing

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->results = [];
        $this->errors = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
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

    public function getImportType(): string
    {
        return $this->importType;
    }

    public function setImportType(string $importType): static
    {
        $this->importType = $importType;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getResults(): ?array
    {
        return $this->results;
    }

    public function setResults(?array $results): static
    {
        $this->results = $results;
        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function setTotalRows(int $totalRows): static
    {
        $this->totalRows = $totalRows;
        return $this;
    }

    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    public function setSuccessfulRows(int $successfulRows): static
    {
        $this->successfulRows = $successfulRows;
        return $this;
    }

    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    public function setFailedRows(int $failedRows): static
    {
        $this->failedRows = $failedRows;
        return $this;
    }

    public function getImportedData(): ?array
    {
        return $this->importedData;
    }

    public function setImportedData(?array $importedData): static
    {
        $this->importedData = $importedData;
        return $this;
    }

    public function addError(string $error, int $row = null): void
    {
        if ($this->errors === null) {
            $this->errors = [];
        }
        
        $this->errors[] = [
            'message' => $error,
            'row' => $row,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function addResult(string $type, string $message, array $data = []): void
    {
        if ($this->results === null) {
            $this->results = [];
        }
        
        if (!isset($this->results[$type])) {
            $this->results[$type] = [];
        }
        
        $this->results[$type][] = [
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function getSuccessRate(): float
    {
        if ($this->totalRows === 0) {
            return 0.0;
        }
        
        return ($this->successfulRows / $this->totalRows) * 100;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'secondary',
            'processing' => 'primary',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'warning',
            default => 'secondary'
        };
    }

    public function getImportTypeLabel(): string
    {
        return match($this->importType) {
            'complete' => 'Complete Import',
            'events_only' => 'Events Only',
            'attendees_only' => 'Attendees Only',
            'agenda_only' => 'Agenda Only',
            'presenters_only' => 'Presenters Only',
            default => 'Unknown'
        };
    }
}