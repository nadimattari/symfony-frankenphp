<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RefreshToken> */
final class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findActiveByHash(string $tokenHash, ?\DateTimeImmutable $now = null): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.tokenHash = :hash')
            ->andWhere('rt.revokedAt IS NULL')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', $now ?? new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Revoke every live session for a user (disabled account, password change). */
    public function revokeAllForUser(User $user, ?\DateTimeImmutable $now = null): int
    {
        return (int) $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revokedAt', ':now')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', $now ?? new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
