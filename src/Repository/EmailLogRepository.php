<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailLog> */
final class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    public function findOneByDedupeKey(string $dedupeKey): ?EmailLog
    {
        return $this->findOneBy(['dedupeKey' => $dedupeKey]);
    }

    /**
     * Insert one attempt or increment the attempt counter for an existing
     * dedupe key. The unique index makes concurrent re-sends safe.
     */
    public function upsert(EmailLog $log): EmailLog
    {
        $em = $this->getEntityManager();

        $run = $log->getRun();
        if (null !== $run && null === $run->getId()) {
            $em->persist($run);
        }
        $em->flush();

        $dedupeKey = $log->getDedupeKey();
        $existing = $this->findOneByDedupeKey($dedupeKey);
        if (null !== $existing) {
            $em->detach($existing);
        }
        if ($em->contains($log)) {
            $em->detach($log);
        }

        $runId = $run?->getId();
        $status = $log->getStatus();
        $messageId = $log->getMessageId();
        $error = $log->getError();
        $sentAt = $log->getSentAt();

        $em->getConnection()->executeStatement(
            'INSERT INTO email_log '
            . '(run_id, dedupe_key, recipient, effective_recipient, status, attempts, '
            . 'message_id, error, created_at, sent_at) '
            . 'VALUES '
            . '(:runId, :dedupeKey, :recipient, :effectiveRecipient, :status, 1, '
            . ':messageId, :error, :createdAt, :sentAt) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'run_id = COALESCE(:dupRunId, run_id), '
            . 'status = :dupStatus, '
            . 'attempts = attempts + 1, '
            . 'message_id = :dupMessageId, '
            . 'error = :dupError, '
            . 'sent_at = CASE WHEN :dupStatusForSent = \'sent\' '
            . 'THEN COALESCE(:dupSentAt, sent_at) ELSE sent_at END',
            [
                'runId' => $runId,
                'dedupeKey' => $dedupeKey,
                'recipient' => $log->getRecipient(),
                'effectiveRecipient' => $log->getEffectiveRecipient(),
                'status' => $status,
                'messageId' => $messageId,
                'error' => $error,
                'createdAt' => $log->getCreatedAt(),
                'sentAt' => $sentAt,
                'dupRunId' => $runId,
                'dupStatus' => $status,
                'dupMessageId' => $messageId,
                'dupError' => $error,
                'dupStatusForSent' => $status,
                'dupSentAt' => $sentAt,
            ],
            [
                'runId' => Types::INTEGER,
                'dedupeKey' => Types::STRING,
                'recipient' => Types::STRING,
                'effectiveRecipient' => Types::STRING,
                'status' => Types::STRING,
                'messageId' => Types::STRING,
                'error' => Types::STRING,
                'createdAt' => Types::DATETIME_IMMUTABLE,
                'sentAt' => Types::DATETIME_IMMUTABLE,
                'dupRunId' => Types::INTEGER,
                'dupStatus' => Types::STRING,
                'dupMessageId' => Types::STRING,
                'dupError' => Types::STRING,
                'dupStatusForSent' => Types::STRING,
                'dupSentAt' => Types::DATETIME_IMMUTABLE,
            ],
        );

        return $this->findOneByDedupeKey($dedupeKey) ?? $log;
    }
}
