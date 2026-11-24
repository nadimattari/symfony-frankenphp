<?php

namespace App\Tests\Unit\Entity;

use App\Entity\RefreshToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    public function testCreatedAtIsInitialisedAndExpiryIsChecked(): void
    {
        $token = (new RefreshToken())
            ->setUser(new User())
            ->setTokenHash(hash('sha256', 'raw-token'))
            ->setExpiresAt(new \DateTimeImmutable('2026-11-25T00:00:00Z'));

        self::assertInstanceOf(\DateTimeImmutable::class, $token->getCreatedAt());
        self::assertFalse($token->isRevoked());
        self::assertFalse($token->isExpired(new \DateTimeImmutable('2026-11-24T00:00:00Z')));
        self::assertTrue($token->isExpired(new \DateTimeImmutable('2026-11-25T00:00:00Z')));

        $token->setRevokedAt(new \DateTimeImmutable('2026-11-24T12:00:00Z'));

        self::assertTrue($token->isRevoked());
    }
}
