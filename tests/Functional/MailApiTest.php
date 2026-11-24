<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MailRun;
use App\Entity\User;
use App\Repository\EmailLogRepository;
use App\Repository\MailRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MailApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $password = 'correct-horse';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->getConnection()->executeStatement('DELETE FROM email_log');
        $this->em->getConnection()->executeStatement('DELETE FROM mail_run');
        $this->em->getConnection()->executeStatement('DELETE FROM refresh_token');
        $this->em->getConnection()->executeStatement('DELETE FROM app_user');

        $user = (new User())
            ->setUsername('ada')
            ->setName('Ada Lovelace')
            ->setRoles([User::USER_ROLE_ADMIN])
            ->setActive(true)
            ->setPassword(password_hash($this->password, PASSWORD_BCRYPT));
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testSettingsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/mail/settings');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSettingsReturnsTheTypedPayload(): void
    {
        $this->client->request('GET', '/api/mail/settings', [], [], $this->authHeaders());

        self::assertResponseIsSuccessful();
        $payload = $this->json();
        self::assertSame('', $payload['from']);
        self::assertSame('', $payload['replyTo']);
        self::assertSame('', $payload['redirectTo']);
        self::assertSame(0, $payload['throttleSeconds']);
        self::assertFalse($payload['isRedirecting']);
        self::assertTrue($payload['testMode']);
    }

    public function testTestSendCreatesACompletedRunAndAnEmailLog(): void
    {
        $this->postTest();

        self::assertResponseIsSuccessful();
        $payload = $this->json();
        self::assertIsInt($payload['runId']);
        self::assertSame(MailRun::STATUS_COMPLETED, $payload['status']);
        self::assertSame('test@example.test', $payload['recipient']);
        self::assertSame('test@example.test', $payload['effectiveRecipient']);

        $run = static::getContainer()->get(MailRunRepository::class)->find($payload['runId']);
        self::assertInstanceOf(MailRun::class, $run);
        self::assertSame(1, $run->getSent());

        $logs = static::getContainer()->get(EmailLogRepository::class)->findBy(['run' => $run]);
        self::assertCount(1, $logs);
        self::assertSame('sent', $logs[0]->getStatus());
    }

    public function testTestSendRejectsAnInvalidRecipient(): void
    {
        $this->postTest(['recipient' => 'not-an-email']);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame('Invalid recipient address.', $this->json()['message']);
    }

    public function testTestSendConflictsWithAnActiveRun(): void
    {
        $run = (new MailRun())->setStatus(MailRun::STATUS_QUEUED);
        $this->em->persist($run);
        $this->em->flush();

        $this->postTest();

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame('A mail run is already in progress.', $this->json()['message']);
    }

    public function testDomainReportRoutesAreNotRegistered(): void
    {
        $this->client->request('GET', '/api/mail/statements', [], [], $this->authHeaders());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /** @param array<string, mixed> $body */
    private function postTest(array $body = []): void
    {
        $headers = $this->authHeaders();
        $headers['CONTENT_TYPE'] = 'application/json';

        $this->client->request(
            'POST',
            '/api/mail/test',
            [],
            [],
            $headers,
            [] === $body ? '{}' : json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['username' => 'ada', 'password' => $this->password], JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $token = $this->json()['token'];
        self::assertIsString($token);

        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    /** @return array<string, mixed> */
    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
