<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

class AuthRateLimitTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $password = 'correct-horse';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        // Symfony's docs recommend clearing this pool between tests. Before the
        // rate-limiter component is installed the service does not exist yet.
        if ($container->has('cache.rate_limiter')) {
            $container->get('cache.rate_limiter')->clear();
        }

        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->getConnection()->executeStatement('DELETE FROM refresh_token');
        $this->em->getConnection()->executeStatement('DELETE FROM app_user');

        $user = (new User())
            ->setUsername('ada')
            ->setName('Ada Lovelace')
            ->setRoles([User::USER_ROLE_ADMIN])
            ->setActive(true);
        $user->setPassword(password_hash($this->password, PASSWORD_BCRYPT));
        $this->em->persist($user);
        $this->em->flush();
    }

    private function postLogin(string $password): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['username' => 'ada', 'password' => $password], JSON_THROW_ON_ERROR));
    }

    private function postRefresh(): void
    {
        $this->client->request('POST', '/api/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    private function postLogout(): void
    {
        $this->client->request('POST', '/api/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    private function assertTooManyRequests(int $limit): void
    {
        self::assertResponseStatusCodeSame(429);

        $response = $this->client->getResponse();
        $retryAfter = $response->headers->get('Retry-After');
        self::assertNotNull($retryAfter);
        self::assertGreaterThan(0, (int) $retryAfter);
        self::assertSame((string) $limit, $response->headers->get('X-RateLimit-Limit'));
        self::assertSame('0', $response->headers->get('X-RateLimit-Remaining'));

        $body = (string) $response->getContent();
        $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Too many authentication attempts. Try again later.', $payload['message'] ?? null);
        self::assertStringNotContainsString('ada', $body);
        self::assertStringNotContainsString('correct-horse', $body);
        self::assertStringNotContainsString('wrong', $body);
    }

    public function testEleventhFailedLoginReturnsJsonTooManyRequests(): void
    {
        for ($attempt = 1; $attempt <= 10; ++$attempt) {
            $this->postLogin('wrong');
            self::assertResponseStatusCodeSame(401);
        }

        $this->postLogin('wrong');
        $this->assertTooManyRequests(10);
    }

    public function testSuccessfulLoginStillWorksAfterClearingLimiterState(): void
    {
        for ($attempt = 1; $attempt <= 3; ++$attempt) {
            $this->postLogin('wrong');
            self::assertResponseStatusCodeSame(401);
        }

        $container = static::getContainer();
        if ($container->has('cache.rate_limiter')) {
            $container->get('cache.rate_limiter')->clear();
        }
        $this->postLogin($this->password);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('token', $payload);
    }

    public function testThirtyFirstRefreshAttemptReturnsJsonTooManyRequests(): void
    {
        for ($attempt = 1; $attempt <= 30; ++$attempt) {
            $this->postRefresh();
            self::assertResponseStatusCodeSame(401);
        }

        $this->postRefresh();
        $this->assertTooManyRequests(30);
    }

    public function testThirtyFirstLogoutAttemptReturnsJsonTooManyRequests(): void
    {
        for ($attempt = 1; $attempt <= 30; ++$attempt) {
            $this->postLogout();
            self::assertResponseStatusCodeSame(204);
        }

        $this->postLogout();
        $this->assertTooManyRequests(30);
    }

    public function testRateLimiterPoolUsesDoctrineDbal(): void
    {
        $container = static::getContainer();
        self::assertTrue($container->has('cache.rate_limiter'));

        $pool = $container->get('cache.rate_limiter');
        if (method_exists($pool, 'getPool')) {
            $pool = $pool->getPool();
        }

        self::assertInstanceOf(DoctrineDbalAdapter::class, $pool);
    }
}
