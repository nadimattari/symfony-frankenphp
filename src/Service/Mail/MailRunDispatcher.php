<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\EmailLog;
use App\Entity\MailRun;
use App\Repository\EmailLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends mail synchronously and records each attempt. No process spawning and
 * no implicit worker: production deployments opt into that separately.
 */
final class MailRunDispatcher
{
    private const TEST_SUBJECT = 'Mail smoke test';
    private const DEFAULT_RECIPIENT = 'test@example.test';
    private const FALLBACK_FROM = 'no-reply@example.test';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MailRouter $router,
        private readonly MailRunManager $runs,
        private readonly EmailLogRepository $logs,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    /**
     * Dev/test use the null or Mailpit transport; elsewhere a redirect must be
     * configured so the starter can never send to a real recipient by accident.
     */
    public function isTestMode(): bool
    {
        return \in_array($this->environment, ['dev', 'test'], true) || $this->router->isRedirecting();
    }

    public function dispatchTest(?string $recipient = null): MailRun
    {
        if (!$this->isTestMode()) {
            throw new \RuntimeException('Mail test sends require a dev/test environment or MAIL_REDIRECT_TO.');
        }

        $recipient = trim($recipient ?? '');
        if ('' === $recipient) {
            $recipient = self::DEFAULT_RECIPIENT;
        }

        $run = $this->runs->create(MailRun::TYPE_TEST, self::TEST_SUBJECT, $recipient);

        return $this->send($run, [[
            'recipient' => $recipient,
            'subject' => self::TEST_SUBJECT,
            'body' => sprintf(
                "Mail backend test.\nRequested recipient: %s\nEffective recipient: %s\n",
                $recipient,
                $this->router->effectiveRecipient($recipient),
            ),
            'dedupeKey' => EmailLog::testKey((int) $run->getId(), $recipient),
        ]]);
    }

    /**
     * @param list<array{recipient: string, subject: string, body: string, dedupeKey: string}> $messages
     */
    private function send(MailRun $run, array $messages): MailRun
    {
        $this->runs->claim($run);

        $total = count($messages);
        $sent = 0;
        $failed = 0;
        $last = $total - 1;

        try {
            foreach ($messages as $index => $message) {
                $recipient = $message['recipient'];
                $effectiveRecipient = $this->router->effectiveRecipient($recipient);
                $sentAt = null;
                $messageId = null;
                $error = null;
                $status = EmailLog::STATUS_FAILED;

                try {
                    $email = $this->buildEmail(
                        $recipient,
                        $effectiveRecipient,
                        $message['subject'],
                        $message['body'],
                    );
                    $result = $this->mailer->send($email);
                    $messageId = $result?->getMessageId()
                        ?? $email->getHeaders()->get('Message-ID')?->getBodyAsString();
                    $sentAt = new \DateTimeImmutable();
                    $status = EmailLog::STATUS_SENT;
                    ++$sent;
                } catch (\Throwable $exception) {
                    ++$failed;
                    $error = mb_substr($exception->getMessage(), 0, 2000);
                }

                $this->logs->upsert((new EmailLog())
                    ->setRun($run)
                    ->setDedupeKey($message['dedupeKey'])
                    ->setRecipient($recipient)
                    ->setEffectiveRecipient($effectiveRecipient)
                    ->setStatus($status)
                    ->setMessageId($messageId)
                    ->setError($error)
                    ->setSentAt($sentAt));

                if ($index < $last && $this->router->getThrottleSeconds() > 0) {
                    sleep($this->router->getThrottleSeconds());
                }
            }

            $this->runs->updateCounters($run, $sent, 0, $failed, $total);
            $this->runs->markCompleted($run, sprintf('sent=%d skipped=0 failed=%d', $sent, $failed));
        } catch (\Throwable $exception) {
            $this->runs->markFailed($run, $exception->getMessage());

            throw $exception;
        }

        return $run;
    }

    private function buildEmail(
        string $recipient,
        string $effectiveRecipient,
        string $subject,
        string $body,
    ): Email {
        $email = (new Email())
            ->from(new Address($this->router->getFrom() ?: self::FALLBACK_FROM))
            ->to(new Address($effectiveRecipient))
            ->subject($this->router->rewriteSubject($subject, $recipient))
            ->text($body);

        if ('' !== $this->router->getReplyTo()) {
            $email->replyTo(new Address($this->router->getReplyTo()));
        }

        $headers = $email->getHeaders();
        $headers->addTextHeader('Auto-Submitted', 'auto-generated');
        foreach ($this->router->headers($recipient) as $name => $value) {
            $headers->addTextHeader($name, $value);
        }
        $headers->addIdHeader('Message-ID', $email->generateMessageId());

        return $email;
    }
}
