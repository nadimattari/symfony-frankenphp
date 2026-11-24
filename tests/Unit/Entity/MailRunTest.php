<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\MailRun;
use PHPUnit\Framework\TestCase;

final class MailRunTest extends TestCase
{
    public function testDefaultsDescribeANewQueuedTestRun(): void
    {
        $run = new MailRun();

        self::assertSame(MailRun::TYPE_TEST, $run->getType());
        self::assertSame(MailRun::STATUS_QUEUED, $run->getStatus());
        self::assertSame(0, $run->getTotal());
        self::assertSame(0, $run->getSent());
        self::assertSame(0, $run->getSkipped());
        self::assertSame(0, $run->getFailed());
        self::assertNull($run->getSubject());
        self::assertNull($run->getRecipient());
        self::assertNull($run->getStartedAt());
        self::assertNull($run->getFinishedAt());
        self::assertNull($run->getMessage());
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getCreatedAt());
    }

    public function testLifecycleFieldsRoundTrip(): void
    {
        $createdAt = new \DateTimeImmutable('2026-11-24 10:00:00');
        $startedAt = new \DateTimeImmutable('2026-11-24 10:00:05');
        $finishedAt = new \DateTimeImmutable('2026-11-24 10:00:09');

        $run = (new MailRun())
            ->setType(MailRun::TYPE_TEST)
            ->setStatus(MailRun::STATUS_COMPLETED)
            ->setSubject('Mail smoke test')
            ->setRecipient('user@example.test')
            ->setCreatedAt($createdAt)
            ->setStartedAt($startedAt)
            ->setFinishedAt($finishedAt)
            ->setTotal(2)
            ->setSent(1)
            ->setSkipped(0)
            ->setFailed(1)
            ->setMessage('sent=1 skipped=0 failed=1');

        self::assertSame(MailRun::STATUS_COMPLETED, $run->getStatus());
        self::assertSame('Mail smoke test', $run->getSubject());
        self::assertSame('user@example.test', $run->getRecipient());
        self::assertSame($createdAt, $run->getCreatedAt());
        self::assertSame($startedAt, $run->getStartedAt());
        self::assertSame($finishedAt, $run->getFinishedAt());
        self::assertSame(2, $run->getTotal());
        self::assertSame(1, $run->getSent());
        self::assertSame(0, $run->getSkipped());
        self::assertSame(1, $run->getFailed());
        self::assertSame('sent=1 skipped=0 failed=1', $run->getMessage());
    }

    public function testStatusConstantsMatchTheAgreedLifecycle(): void
    {
        self::assertSame('queued', MailRun::STATUS_QUEUED);
        self::assertSame('running', MailRun::STATUS_RUNNING);
        self::assertSame('completed', MailRun::STATUS_COMPLETED);
        self::assertSame('failed', MailRun::STATUS_FAILED);
        self::assertSame(120, MailRun::QUEUED_TIMEOUT_SECONDS);
        self::assertSame(7200, MailRun::RUNNING_TIMEOUT_SECONDS);
    }
}
