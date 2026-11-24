<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\MailRun;
use App\Repository\MailRunRepository;
use Doctrine\ORM\EntityManagerInterface;

/** Creates, claims, reaps and finishes mail runs. */
final class MailRunManager
{
    public function __construct(
        private readonly MailRunRepository $runs,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function create(
        string $type = MailRun::TYPE_TEST,
        ?string $subject = null,
        ?string $recipient = null,
    ): MailRun {
        $this->reapStaleRuns();

        if (null !== $this->runs->findActiveRun($type)) {
            throw new ActiveMailRunException($type);
        }

        $run = (new MailRun())
            ->setType($type)
            ->setStatus(MailRun::STATUS_QUEUED)
            ->setSubject($subject)
            ->setRecipient($recipient);

        $this->em->persist($run);
        $this->em->flush();

        return $run;
    }

    public function claim(MailRun $run, ?\DateTimeImmutable $now = null): void
    {
        // Re-read the row so a stale in-memory entity cannot claim twice.
        $current = $this->runs->find($run->getId());
        if (null === $current || MailRun::STATUS_QUEUED !== $current->getStatus()) {
            throw new ActiveMailRunException($run->getType());
        }

        $run->setStatus(MailRun::STATUS_RUNNING)
            ->setStartedAt($now ?? new \DateTimeImmutable())
            ->setFinishedAt(null)
            ->setMessage(null);
        $this->em->flush();
    }

    public function updateCounters(
        MailRun $run,
        int $sent,
        int $skipped,
        int $failed,
        ?int $total = null,
    ): void {
        $run->setSent($sent)
            ->setSkipped($skipped)
            ->setFailed($failed);
        if (null !== $total) {
            $run->setTotal($total);
        }
        $this->em->flush();
    }

    public function markFailed(MailRun $run, string $message, ?\DateTimeImmutable $now = null): void
    {
        $run->setStatus(MailRun::STATUS_FAILED)
            ->setFinishedAt($now ?? new \DateTimeImmutable())
            ->setMessage(mb_substr($message, 0, 2000));
        $this->em->flush();
    }

    public function markCompleted(MailRun $run, string $message, ?\DateTimeImmutable $now = null): void
    {
        $run->setStatus(MailRun::STATUS_COMPLETED)
            ->setFinishedAt($now ?? new \DateTimeImmutable())
            ->setMessage(mb_substr($message, 0, 2000));
        $this->em->flush();
    }

    /** Mark abandoned runs failed. Returns the number of runs reaped. */
    public function reapStaleRuns(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $stale = $this->runs->findStaleRuns($now);
        if ([] === $stale) {
            return 0;
        }

        foreach ($stale as $run) {
            $reason = MailRun::STATUS_QUEUED === $run->getStatus()
                ? sprintf('Run never started (queued timeout after %d seconds).', MailRun::QUEUED_TIMEOUT_SECONDS)
                : sprintf('Run timed out after %d seconds.', MailRun::RUNNING_TIMEOUT_SECONDS);
            $run->setStatus(MailRun::STATUS_FAILED)
                ->setFinishedAt($now)
                ->setMessage($reason);
        }
        $this->em->flush();

        return count($stale);
    }
}
