<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Security\AuthCookies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class AuthFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private RefreshTokenRepository $tokens;
    private string $password = 'correct-horse';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->tokens = $container->get(RefreshTokenRepository::class);

        $this->em->getConnection()->executeStatement('DELETE FROM refresh_token');
        $this->em->getConnection()->executeStatement('DELETE FROM app_user');

        $user = (new User())
            ->setUsername('ada')
            ->setName('Ada Lovelace')
            ->setRoles([User::USER_ROLE_ADMIN])
            ->setActive(true);
        // Symfony's `auto` hasher verifies native password hashes, so a bcrypt
        // hash is enough here and keeps the test independent of private services.
        $user->setPassword(password_hash($this->password, PASSWORD_BCRYPT));
        $this->em->persist($user);
        $this->em->flush();
    }

    /** BrowserKit's get() defaults to path '/', but refresh_token is /api-scoped. */
    private function cookie(string $name): Cookie
    {
        $path = AuthCookies::REFRESH_NAME === $name ? '/api' : '/';
        $cookie = $this->client->getCookieJar()->get($name, $path, 'localhost');
        self::assertInstanceOf(Cookie::class, $cookie, sprintf('Missing %s cookie', $name));

        return $cookie;
    }

    /** @return array<string, mixed> */
    private function jwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        self::assertCount(3, $parts);
        $encoded = strtr($parts[1], '-_', '+/');

        return json_decode(
            base64_decode($encoded.str_repeat('=', (4 - strlen($encoded) % 4) % 4), true),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /** @return array{payload: array, cookies: array<string, Cookie>} */
    private function login(): array
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['username' => 'ada', 'password' => $this->password], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        return [
            'payload' => json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
            'cookies' => [
                AuthCookies::REFRESH_NAME => $this->cookie(AuthCookies::REFRESH_NAME),
                AuthCookies::CSRF_NAME => $this->cookie(AuthCookies::CSRF_NAME),
            ],
        ];
    }

    public function testLoginReturnsJwtAndSetsRefreshAndCsrfCookies(): void
    {
        $payload = $this->login()['payload'];

        self::assertArrayHasKey('token', $payload);
        self::assertSame(3, count(explode('.', $payload['token'])));
        self::assertSame(3600, $payload['expiresIn']);
        self::assertSame('Ada Lovelace', $payload['data']['name']);
        self::assertContains(User::USER_ROLE_ADMIN, $payload['data']['roles']);

        $refresh = $this->cookie(AuthCookies::REFRESH_NAME);
        self::assertTrue($refresh->isHttpOnly());
        self::assertSame('/api', $refresh->getPath());

        $csrf = $this->cookie(AuthCookies::CSRF_NAME);
        self::assertFalse($csrf->isHttpOnly());
        self::assertSame('/', $csrf->getPath());
    }

    public function testRefreshRotatesTheCookieAndReturnsANewJwt(): void
    {
        $login = $this->login();
        $oldRefresh = $login['cookies'][AuthCookies::REFRESH_NAME]->getValue();
        $oldHash = hash('sha256', $oldRefresh);

        // BrowserKit sends the jar's cookies on the next request automatically.
        $this->client->request('POST', '/api/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $login['cookies'][AuthCookies::CSRF_NAME]->getValue(),
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        // Lexik's payload is deterministic within a second (iat/exp only), so
        // the string may match. What matters is a freshly signed token for the
        // same user and a rotated refresh cookie (asserted below).
        self::assertSame('ada', $this->jwtPayload($payload['token'])['username']);
        self::assertSame($this->jwtPayload($login['payload']['token'])['username'], $this->jwtPayload($payload['token'])['username']);
        self::assertSame('Ada Lovelace', $payload['data']['name']);

        $newRefresh = $this->cookie(AuthCookies::REFRESH_NAME);
        self::assertNotSame($oldRefresh, $newRefresh->getValue());
        self::assertTrue($this->tokens->findOneBy(['tokenHash' => $oldHash])->isRevoked());
        self::assertNotNull($this->tokens->findActiveByHash(hash('sha256', $newRefresh->getValue()), new \DateTimeImmutable()));
    }

    public function testRefreshWithoutCsrfHeaderIsForbidden(): void
    {
        $this->login();

        $this->client->request('POST', '/api/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testRefreshWithUnknownTokenIsUnauthorized(): void
    {
        $login = $this->login();
        $this->client->getCookieJar()->set(new Cookie(
            AuthCookies::REFRESH_NAME,
            str_repeat('a', 64),
            0,
            '/api',
            'localhost',
        ));

        $this->client->request('POST', '/api/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $login['cookies'][AuthCookies::CSRF_NAME]->getValue(),
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutRevokesAndClearsCookies(): void
    {
        $login = $this->login();
        $hash = hash('sha256', $login['cookies'][AuthCookies::REFRESH_NAME]->getValue());

        $this->client->request('POST', '/api/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $login['cookies'][AuthCookies::CSRF_NAME]->getValue(),
        ]);

        self::assertResponseStatusCodeSame(204);
        $stored = $this->tokens->findOneBy(['tokenHash' => $hash]);
        self::assertInstanceOf(\App\Entity\RefreshToken::class, $stored);
        self::assertTrue($stored->isRevoked());
        self::assertStringContainsString('refresh_token=', (string) $this->client->getResponse()->headers->get('Set-Cookie'));
    }

    public function testDisabledUserCannotLogIn(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'ada']);
        self::assertInstanceOf(User::class, $user);
        $user->setActive(false);
        $this->em->flush();

        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['username' => 'ada', 'password' => $this->password], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testBadCredentialsReturn401(): void
    {
        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['username' => 'ada', 'password' => 'wrong'], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRequiresABearerToken(): void
    {
        $this->client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testBearerTokenFromLoginAuthenticatesProtectedRoute(): void
    {
        $token = $this->login()['payload']['token'];
        $this->client->getCookieJar()->clear();

        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'username' => 'ada',
            'name' => 'Ada Lovelace',
            'roles' => [User::USER_ROLE_ADMIN, User::USER_ROLE_USER],
        ], json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testApiPlatformDocsAreNotRegisteredOutsideDev(): void
    {
        $this->client->request('GET', '/api/docs');

        self::assertResponseStatusCodeSame(404);
    }
}
