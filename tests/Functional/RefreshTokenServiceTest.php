<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Service\Auth\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RefreshTokenServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RefreshTokenService $service;
    private RefreshTokenRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->service = $container->get(RefreshTokenService::class);
        $this->repository = $container->get(RefreshTokenRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM refresh_token');
        $this->em->getConnection()->executeStatement('DELETE FROM app_user');

        $this->user = (new User())
            ->setUsername('ada')
            ->setName('Ada Lovelace')
            ->setPassword('hashed-password')
            ->setRoles([User::USER_ROLE_ADMIN]);
        $this->em->persist($this->user);
        $this->em->flush();
    }

    public function testIssueStoresOnlyTheSha256Hash(): void
    {
        $issued = $this->service->issue($this->user);

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $issued['token']);
        self::assertGreaterThan(new \DateTimeImmutable(), $issued['expiresAt']);

        $row = $this->repository->findOneBy(['tokenHash' => hash('sha256', $issued['token'])]);
        self::assertNotNull($row);
        self::assertSame($this->user->getId(), $row->getUser()->getId());
        self::assertNotSame($issued['token'], $row->getTokenHash());
        self::assertNull($this->repository->findOneBy(['tokenHash' => $issued['token']]));
    }

    public function testRotationRevokesTheOldTokenAndRejectsReplay(): void
    {
        $first = $this->service->issue($this->user);
        $firstHash = hash('sha256', $first['token']);

        $rotated = $this->service->rotate($first['token']);

        self::assertNotNull($rotated);
        self::assertSame($this->user->getId(), $rotated['user']->getId());
        self::assertNotSame($first['token'], $rotated['token']);
        self::assertNotNull($this->repository->findActiveByHash(hash('sha256', $rotated['token']), new \DateTimeImmutable()));
        self::assertNull($this->repository->findActiveByHash($firstHash, new \DateTimeImmutable()));

        self::assertNull($this->service->rotate($first['token']));
    }

    public function testRevokeClearsTheActiveSession(): void
    {
        $issued = $this->service->issue($this->user);
        $hash = hash('sha256', $issued['token']);

        $this->service->revoke($issued['token']);

        self::assertNull($this->repository->findActiveByHash($hash, new \DateTimeImmutable()));
        self::assertTrue($this->repository->findOneBy(['tokenHash' => $hash])->isRevoked());
    }

    public function testRevokeAllForUserClosesEverySession(): void
    {
        $first = $this->service->issue($this->user);
        $second = $this->service->issue($this->user);

        $count = $this->repository->revokeAllForUser($this->user, new \DateTimeImmutable());

        self::assertSame(2, $count);
        self::assertNull($this->service->rotate($first['token']));
        self::assertNull($this->service->rotate($second['token']));
    }

    public function testExpiredTokenIsNotActive(): void
    {
        $issued = $this->service->issue($this->user);

        self::assertNull(
            $this->repository->findActiveByHash(hash('sha256', $issued['token']), new \DateTimeImmutable('+31 days')),
        );
    }
}
