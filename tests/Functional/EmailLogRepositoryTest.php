<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\EmailLog;
use App\Entity\MailRun;
use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EmailLogRepositoryTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private EmailLogRepository $logs;
    private MailRun $run;

    protected function setUp(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->logs = $container->get(EmailLogRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM email_log');
        $this->em->getConnection()->executeStatement('DELETE FROM mail_run');
        $this->em->clear();

        $this->run = (new MailRun())->setStatus(MailRun::STATUS_RUNNING)->setStartedAt(new \DateTimeImmutable());
        $this->em->persist($this->run);
        $this->em->flush();
    }

    public function testUpsertInsertsThenIncrementsAttemptsForTheSameKey(): void
    {
        $createdAt = new \DateTimeImmutable('2026-11-24 12:00:00');
        $sentAt = new \DateTimeImmutable('2026-11-24 12:00:01');
        $first = $this->log('test:1:user@example.test', EmailLog::STATUS_SENT, '<first@example.test>', null, $createdAt, $sentAt);

        $stored = $this->logs->upsert($first);

        self::assertNotNull($stored->getId());
        self::assertSame(1, $stored->getAttempts());
        self::assertSame(EmailLog::STATUS_SENT, $stored->getStatus());
        self::assertEquals($sentAt, $stored->getSentAt());

        $retry = $this->log('test:1:user@example.test', EmailLog::STATUS_FAILED, null, 'SMTP down', $createdAt, null);
        $updated = $this->logs->upsert($retry);

        self::assertSame($stored->getId(), $updated->getId());
        self::assertSame(2, $updated->getAttempts());
        self::assertSame(EmailLog::STATUS_FAILED, $updated->getStatus());
        self::assertSame('SMTP down', $updated->getError());
        self::assertEquals($createdAt, $updated->getCreatedAt());
        self::assertEquals($sentAt, $updated->getSentAt());
    }

    public function testUpsertStoresANewSentAtOnAResend(): void
    {
        $this->logs->upsert($this->log('test:2:user@example.test', EmailLog::STATUS_FAILED));

        $resentAt = new \DateTimeImmutable('2026-11-24 12:05:00');
        $updated = $this->logs->upsert($this->log(
            'test:2:user@example.test',
            EmailLog::STATUS_SENT,
            '<second@example.test>',
            null,
            null,
            $resentAt,
        ));

        self::assertSame(2, $updated->getAttempts());
        self::assertSame(EmailLog::STATUS_SENT, $updated->getStatus());
        self::assertSame('<second@example.test>', $updated->getMessageId());
        self::assertEquals($resentAt, $updated->getSentAt());
    }

    public function testDedupeKeyIsUniqueInTheDatabase(): void
    {
        $this->em->persist($this->log('test:3:user@example.test', EmailLog::STATUS_SENT));
        $this->em->persist($this->log('test:3:user@example.test', EmailLog::STATUS_FAILED));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    private function log(
        string $dedupeKey,
        string $status,
        ?string $messageId = null,
        ?string $error = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $sentAt = null,
    ): EmailLog {
        return (new EmailLog())
            ->setRun($this->run)
            ->setDedupeKey($dedupeKey)
            ->setRecipient('user@example.test')
            ->setEffectiveRecipient('redirect@example.test')
            ->setStatus($status)
            ->setMessageId($messageId)
            ->setError($error)
            ->setCreatedAt($createdAt ?? new \DateTimeImmutable())
            ->setSentAt($sentAt);
    }
}
