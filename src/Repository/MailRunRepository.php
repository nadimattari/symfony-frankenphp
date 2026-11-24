<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MailRun> */
final class MailRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailRun::class);
    }

    public function findActiveRun(string $type): ?MailRun
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.type = :type')
            ->andWhere('r.status = :queued OR r.status = :running')
            ->setParameter('type', $type)
            ->setParameter('queued', MailRun::STATUS_QUEUED)
            ->setParameter('running', MailRun::STATUS_RUNNING)
            ->orderBy('r.createdAt', \SortDirection::Descending)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<MailRun> */
    public function findRecentRuns(int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', \SortDirection::Descending)
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * Queued runs that never booted and running runs whose progress has gone
     * stale. The reference thresholds live on the entity as constants.
     *
     * @return list<MailRun>
     */
    public function findStaleRuns(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $queuedCutoff = $now->modify(sprintf('-%d seconds', MailRun::QUEUED_TIMEOUT_SECONDS));
        $runningCutoff = $now->modify(sprintf('-%d seconds', MailRun::RUNNING_TIMEOUT_SECONDS));

        return $this->createQueryBuilder('r')
            ->andWhere('(r.status = :queued AND r.createdAt < :queuedCutoff) OR (r.status = :running AND COALESCE(r.startedAt, r.createdAt) < :runningCutoff)')
            ->setParameter('queued', MailRun::STATUS_QUEUED)
            ->setParameter('running', MailRun::STATUS_RUNNING)
            ->setParameter('queuedCutoff', $queuedCutoff)
            ->setParameter('runningCutoff', $runningCutoff)
            ->orderBy('r.createdAt', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }
}
