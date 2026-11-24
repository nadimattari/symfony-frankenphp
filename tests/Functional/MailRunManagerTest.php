<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MailRun;
use App\Repository\MailRunRepository;
use App\Service\Mail\ActiveMailRunException;
use App\Service\Mail\MailRunManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MailRunManagerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private MailRunRepository $runs;
    private MailRunManager $manager;

    protected function setUp(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->runs = $container->get(MailRunRepository::class);
        $this->manager = $container->get(MailRunManager::class);

        $this->em->getConnection()->executeStatement('DELETE FROM email_log');
        $this->em->getConnection()->executeStatement('DELETE FROM mail_run');
        $this->em->clear();
    }

    public function testCreatePersistsAQueuedRunAndRejectsASecondActiveRun(): void
    {
        $run = $this->manager->create(MailRun::TYPE_TEST, 'Mail smoke test', 'user@example.test');

        self::assertNotNull($run->getId());
        self::assertSame(MailRun::STATUS_QUEUED, $run->getStatus());
        self::assertSame('Mail smoke test', $run->getSubject());
        self::assertSame('user@example.test', $run->getRecipient());

        try {
            $this->manager->create(MailRun::TYPE_TEST);
            self::fail('A second active run of the same type should be rejected.');
        } catch (ActiveMailRunException $exception) {
            self::assertSame(MailRun::TYPE_TEST, $exception->runType);
        }
    }

    public function testClaimMovesAQueuedRunToRunningAndCannotClaimTwice(): void
    {
        $run = $this->manager->create(MailRun::TYPE_TEST);
        $startedAt = new \DateTimeImmutable('2026-11-24 12:00:00');

        $this->manager->claim($run, $startedAt);

        self::assertSame(MailRun::STATUS_RUNNING, $run->getStatus());
        self::assertEquals($startedAt, $run->getStartedAt());
        self::assertNull($run->getFinishedAt());

        $this->expectException(ActiveMailRunException::class);
        $this->manager->claim($run, $startedAt);
    }

    public function testReapStaleRunsFailsQueuedAndTimedOutRuns(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 12:00:00');
        $staleQueued = (new MailRun())
            ->setStatus(MailRun::STATUS_QUEUED)
            ->setCreatedAt($now->modify('-121 seconds'));
        $staleRunning = (new MailRun())
            ->setStatus(MailRun::STATUS_RUNNING)
            ->setCreatedAt($now->modify('-3 hours'))
            ->setStartedAt($now->modify('-7201 seconds'));
        $fresh = (new MailRun())
            ->setStatus(MailRun::STATUS_QUEUED)
            ->setCreatedAt($now->modify('-119 seconds'));
        foreach ([$staleQueued, $staleRunning, $fresh] as $run) {
            $this->em->persist($run);
        }
        $this->em->flush();

        $count = $this->manager->reapStaleRuns($now);

        self::assertSame(2, $count);
        self::assertSame(MailRun::STATUS_FAILED, $staleQueued->getStatus());
        self::assertSame('Run never started (queued timeout after 120 seconds).', $staleQueued->getMessage());
        self::assertSame(MailRun::STATUS_FAILED, $staleRunning->getStatus());
        self::assertSame('Run timed out after 7200 seconds.', $staleRunning->getMessage());
        self::assertSame(MailRun::STATUS_QUEUED, $fresh->getStatus());
    }

    public function testMarkFailedTruncatesTheMessageAndFinishesTheRun(): void
    {
        $run = $this->manager->create(MailRun::TYPE_TEST);
        $this->manager->claim($run);

        $this->manager->markFailed($run, str_repeat('x', 3000));

        self::assertSame(MailRun::STATUS_FAILED, $run->getStatus());
        self::assertSame(2000, strlen($run->getMessage()));
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getFinishedAt());
    }

    public function testUpdateCountersAndMarkCompletedPersistTheSummary(): void
    {
        $run = $this->manager->create(MailRun::TYPE_TEST);
        $this->manager->claim($run);

        $this->manager->updateCounters($run, 2, 1, 0, 3);
        $this->manager->markCompleted($run, 'sent=2 skipped=1 failed=0');

        self::assertSame(3, $run->getTotal());
        self::assertSame(2, $run->getSent());
        self::assertSame(1, $run->getSkipped());
        self::assertSame(0, $run->getFailed());
        self::assertSame(MailRun::STATUS_COMPLETED, $run->getStatus());
        self::assertSame('sent=2 skipped=1 failed=0', $run->getMessage());
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getFinishedAt());
    }
}
