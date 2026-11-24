<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MailRun;
use App\Repository\MailRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MailRunRepositoryTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private MailRunRepository $runs;

    protected function setUp(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->runs = $container->get(MailRunRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM email_log');
        $this->em->getConnection()->executeStatement('DELETE FROM mail_run');
        $this->em->clear();
    }

    public function testFindActiveRunReturnsNewestQueuedOrRunningRunOfType(): void
    {
        $this->persistRun(MailRun::STATUS_COMPLETED, new \DateTimeImmutable('2026-11-24 12:00:00'));
        $oldQueued = $this->persistRun(MailRun::STATUS_QUEUED, new \DateTimeImmutable('2026-11-24 12:01:00'));
        $newRunning = $this->persistRun(MailRun::STATUS_RUNNING, new \DateTimeImmutable('2026-11-24 12:02:00'));
        $this->persistRun(MailRun::STATUS_FAILED, new \DateTimeImmutable('2026-11-24 12:03:00'));
        $this->persistRun(MailRun::STATUS_QUEUED, new \DateTimeImmutable('2026-11-24 12:04:00'), null, 'other');
        $this->em->flush();

        $active = $this->runs->findActiveRun(MailRun::TYPE_TEST);

        self::assertInstanceOf(MailRun::class, $active);
        self::assertSame($newRunning->getId(), $active->getId());
        self::assertNotSame($oldQueued->getId(), $active->getId());
    }

    public function testFindStaleRunsUsesQueuedAndRunningThresholds(): void
    {
        $now = new \DateTimeImmutable('2026-11-24 12:00:00');
        $staleQueued = $this->persistRun(MailRun::STATUS_QUEUED, $now->modify('-121 seconds'));
        $this->persistRun(MailRun::STATUS_QUEUED, $now->modify('-119 seconds'));
        $staleRunning = $this->persistRun(MailRun::STATUS_RUNNING, $now->modify('-3 hours'), $now->modify('-7201 seconds'));
        $this->persistRun(MailRun::STATUS_RUNNING, $now->modify('-3 hours'), $now->modify('-7199 seconds'));
        $staleNoStart = $this->persistRun(MailRun::STATUS_RUNNING, $now->modify('-3 hours'));
        $this->persistRun(MailRun::STATUS_COMPLETED, $now->modify('-1 day'));
        $this->em->flush();

        $ids = array_map(
            static fn (MailRun $run): int => $run->getId(),
            $this->runs->findStaleRuns($now),
        );

        self::assertContains($staleQueued->getId(), $ids);
        self::assertContains($staleRunning->getId(), $ids);
        self::assertContains($staleNoStart->getId(), $ids);
        self::assertCount(3, $ids);
    }

    /** @param 'queued'|'running'|'completed'|'failed' $status */
    private function persistRun(
        string $status,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $startedAt = null,
        string $type = MailRun::TYPE_TEST,
    ): MailRun {
        $run = (new MailRun())
            ->setType($type)
            ->setStatus($status)
            ->setCreatedAt($createdAt)
            ->setStartedAt($startedAt);

        $this->em->persist($run);

        return $run;
    }
}
