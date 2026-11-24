<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One attempted message. Message bodies and attachments are never persisted.
 */
#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\Table(name: 'email_log')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL_LOG_DEDUPE_KEY', fields: ['dedupeKey'])]
#[ORM\Index(name: 'IDX_EMAIL_LOG_RUN', columns: ['run_id'])]
#[ORM\Index(name: 'IDX_EMAIL_LOG_STATUS', columns: ['status'])]
class EmailLog
{
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MailRun::class)]
    #[ORM\JoinColumn(name: 'run_id', nullable: true, onDelete: 'SET NULL')]
    private ?MailRun $run = null;

    #[ORM\Column(length: 191)]
    private string $dedupeKey;

    #[ORM\Column(length: 255)]
    private string $recipient;

    #[ORM\Column(length: 255)]
    private string $effectiveRecipient;

    #[ORM\Column(length: 16)]
    private string $status = self::STATUS_FAILED;

    #[ORM\Column]
    private int $attempts = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $messageId = null;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /** Stable, case-insensitive key for the starter's single test message. */
    public static function testKey(int $runId, string $recipient): string
    {
        return sprintf('test:%d:%s', $runId, mb_strtolower(trim($recipient)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRun(): ?MailRun
    {
        return $this->run;
    }

    public function setRun(?MailRun $run): static
    {
        $this->run = $run;

        return $this;
    }

    public function getDedupeKey(): string
    {
        return $this->dedupeKey;
    }

    public function setDedupeKey(string $dedupeKey): static
    {
        $this->dedupeKey = $dedupeKey;

        return $this;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getEffectiveRecipient(): string
    {
        return $this->effectiveRecipient;
    }

    public function setEffectiveRecipient(string $effectiveRecipient): static
    {
        $this->effectiveRecipient = $effectiveRecipient;

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

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): static
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
