<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MailRunRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One durable mail send/run. The starter uses the generic `test` type;
 * domain-specific run types are deliberately out of scope.
 */
#[ORM\Entity(repositoryClass: MailRunRepository::class)]
#[ORM\Table(name: 'mail_run')]
#[ORM\Index(name: 'IDX_MAIL_RUN_TYPE_CREATED', columns: ['type', 'created_at'])]
#[ORM\Index(name: 'IDX_MAIL_RUN_STATUS', columns: ['status'])]
class MailRun
{
    public const TYPE_TEST = 'test';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /** A queued run older than this never booted. */
    public const QUEUED_TIMEOUT_SECONDS = 120;

    /** A running run with no progress after this long is considered timed out. */
    public const RUNNING_TIMEOUT_SECONDS = 7200;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $type = self::TYPE_TEST;

    #[ORM\Column(length: 16)]
    private string $status = self::STATUS_QUEUED;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipient = null;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column]
    private int $sent = 0;

    #[ORM\Column]
    private int $skipped = 0;

    #[ORM\Column]
    private int $failed = 0;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getSent(): int
    {
        return $this->sent;
    }

    public function setSent(int $sent): static
    {
        $this->sent = $sent;

        return $this;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function setSkipped(int $skipped): static
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): static
    {
        $this->failed = $failed;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }
}
