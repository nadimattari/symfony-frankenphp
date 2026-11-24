<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\EmailLog;
use App\Entity\MailRun;
use PHPUnit\Framework\TestCase;

final class EmailLogTest extends TestCase
{
    public function testDefaultsDescribeAFailedAttempt(): void
    {
        $log = new EmailLog();

        self::assertSame(EmailLog::STATUS_FAILED, $log->getStatus());
        self::assertSame(1, $log->getAttempts());
        self::assertNull($log->getRun());
        self::assertNull($log->getMessageId());
        self::assertNull($log->getError());
        self::assertNull($log->getSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
    }

    public function testFieldsRoundTrip(): void
    {
        $run = new MailRun();
        $createdAt = new \DateTimeImmutable('2026-11-24 11:00:00');
        $sentAt = new \DateTimeImmutable('2026-11-24 11:00:01');

        $log = (new EmailLog())
            ->setRun($run)
            ->setDedupeKey('test:7:user@example.test')
            ->setRecipient('user@example.test')
            ->setEffectiveRecipient('redirect@example.test')
            ->setStatus(EmailLog::STATUS_SENT)
            ->setAttempts(3)
            ->setMessageId('<abc@example.test>')
            ->setError(null)
            ->setCreatedAt($createdAt)
            ->setSentAt($sentAt);

        self::assertSame($run, $log->getRun());
        self::assertSame('test:7:user@example.test', $log->getDedupeKey());
        self::assertSame('user@example.test', $log->getRecipient());
        self::assertSame('redirect@example.test', $log->getEffectiveRecipient());
        self::assertSame(EmailLog::STATUS_SENT, $log->getStatus());
        self::assertSame(3, $log->getAttempts());
        self::assertSame('<abc@example.test>', $log->getMessageId());
        self::assertNull($log->getError());
        self::assertSame($createdAt, $log->getCreatedAt());
        self::assertSame($sentAt, $log->getSentAt());
    }

    public function testTestKeyNormalisesTheRecipient(): void
    {
        self::assertSame('test:12:user@example.test', EmailLog::testKey(12, '  User@Example.test  '));
    }
}
