<?php

namespace App\Service\Auth;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Issues, rotates and revokes refresh tokens. Raw values exist only in the
 * response cookie; the database stores their SHA-256 hash. Rotation revokes
 * the presented token in the same flush that persists its replacement.
 */
final class RefreshTokenService
{
    public const TTL_DAYS = 30;

    public function __construct(
        private readonly RefreshTokenRepository $tokens,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array{token: string, expiresAt: \DateTimeImmutable} */
    public function issue(User $user): array
    {
        $issued = $this->create($user, new \DateTimeImmutable());
        $this->em->flush();

        return $issued;
    }

    /**
     * @return array{user: User, token: string, expiresAt: \DateTimeImmutable}|null
     */
    public function rotate(string $rawToken): ?array
    {
        $now = new \DateTimeImmutable();
        $token = $this->tokens->findActiveByHash(self::hash($rawToken), $now);

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user->isActive()) {
            $this->tokens->revokeAllForUser($user, $now);
            $this->em->flush();

            return null;
        }

        $token->setRevokedAt($now);
        $issued = $this->create($user, $now);
        $this->em->flush();

        return ['user' => $user] + $issued;
    }

    public function revoke(string $rawToken): void
    {
        $token = $this->tokens->findOneBy(['tokenHash' => self::hash($rawToken)]);

        if (null === $token || $token->isRevoked()) {
            return;
        }

        $token->setRevokedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function revokeAllForUser(User $user): void
    {
        $this->tokens->revokeAllForUser($user, new \DateTimeImmutable());
        $this->em->flush();
    }

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /** @return array{token: string, expiresAt: \DateTimeImmutable} */
    private function create(User $user, \DateTimeImmutable $now): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = $now->modify(sprintf('+%d days', self::TTL_DAYS));

        $token = (new RefreshToken())
            ->setUser($user)
            ->setTokenHash(self::hash($rawToken))
            ->setExpiresAt($expiresAt);
        $this->em->persist($token);

        return ['token' => $rawToken, 'expiresAt' => $expiresAt];
    }
}
