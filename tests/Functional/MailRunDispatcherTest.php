<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\EmailLog;
use App\Entity\MailRun;
use App\Repository\EmailLogRepository;
use App\Repository\MailRunRepository;
use App\Service\Mail\MailRouter;
use App\Service\Mail\MailRunDispatcher;
use App\Tests\Support\RecordingTransport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

final class MailRunDispatcherTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private RecordingTransport $transport;

    protected function setUp(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->getConnection()->executeStatement('DELETE FROM email_log');
        $this->em->getConnection()->executeStatement('DELETE FROM mail_run');
        $this->em->clear();

        $this->transport = new RecordingTransport();
        $container->set('mailer.transports', $this->transport);
        $container->set('mailer.mailer', new Mailer($this->transport));
    }

    private function dispatcher(): MailRunDispatcher
    {
        return static::getContainer()->get(MailRunDispatcher::class);
    }

    public function testDispatchTestSendsOneMessageAndRecordsASuccessfulRun(): void
    {
        $run = $this->dispatcher()->dispatchTest('user@example.test');

        self::assertSame(MailRun::STATUS_COMPLETED, $run->getStatus());
        self::assertSame(1, $run->getTotal());
        self::assertSame(1, $run->getSent());
        self::assertSame(0, $run->getFailed());
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getStartedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $run->getFinishedAt());

        $logs = static::getContainer()->get(EmailLogRepository::class)->findBy(['run' => $run]);
        self::assertCount(1, $logs);
        $log = $logs[0];
        self::assertSame(EmailLog::STATUS_SENT, $log->getStatus());
        self::assertSame('user@example.test', $log->getRecipient());
        self::assertSame('user@example.test', $log->getEffectiveRecipient());
        self::assertNotNull($log->getMessageId());

        self::assertCount(1, $this->transport->messages);
        $message = $this->transport->messages[0];
        self::assertInstanceOf(Email::class, $message);
        self::assertSame('Mail smoke test', $message->getSubject());
        self::assertSame('user@example.test', $message->getTo()[0]->getAddress());
        self::assertTrue($message->getHeaders()->has('Auto-Submitted'));
        self::assertTrue($message->getHeaders()->has('Message-ID'));
        self::assertFalse($message->getHeaders()->has('X-Original-Recipient'));
    }

    public function testDispatchTestAppliesTheConfiguredRedirect(): void
    {
        $container = static::getContainer();
        $container->set(MailRouter::class, new MailRouter(
            'from@example.test',
            'reply@example.test',
            'redirect@example.test',
            0,
        ));
        $dispatcher = $container->get(MailRunDispatcher::class);

        $dispatcher->dispatchTest('user@example.test');

        self::assertCount(1, $this->transport->messages);
        $message = $this->transport->messages[0];
        self::assertInstanceOf(Email::class, $message);
        self::assertSame('redirect@example.test', $message->getTo()[0]->getAddress());
        self::assertSame('[TEST] Mail smoke test (for user@example.test)', $message->getSubject());
        self::assertSame(
            'user@example.test',
            $message->getHeaders()->get('X-Original-Recipient')?->getBodyAsString(),
        );
    }

    public function testDispatchTestDefaultsToANonRoutableRecipient(): void
    {
        $this->dispatcher()->dispatchTest();

        $logs = static::getContainer()->get(EmailLogRepository::class)->findAll();
        self::assertCount(1, $logs);
        self::assertSame('test@example.test', $logs[0]->getRecipient());
    }
}
