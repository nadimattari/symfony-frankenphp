<?php

declare(strict_types=1);

namespace App\Service\Mail;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Pure mail configuration helper. It resolves the redirect and derives the
 * per-message recipient, subject and headers; it never sends anything.
 */
final class MailRouter
{
    private string $from;
    private string $replyTo;
    private string $redirectTo;
    private int $throttleSeconds;

    public function __construct(
        #[Autowire('%app.mail_from%')]
        string $from,
        #[Autowire('%app.mail_reply_to%')]
        string $replyTo,
        #[Autowire('%app.mail_redirect_to%')]
        string $redirectTo,
        #[Autowire('%app.mail_throttle_seconds%')]
        int $throttleSeconds,
    ) {
        $this->from = trim($from);
        $this->replyTo = trim($replyTo);
        $this->redirectTo = trim($redirectTo);
        $this->throttleSeconds = max(0, $throttleSeconds);
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function getRedirectTo(): string
    {
        return $this->redirectTo;
    }

    public function getThrottleSeconds(): int
    {
        return $this->throttleSeconds;
    }

    public function isRedirecting(): bool
    {
        return '' !== $this->redirectTo;
    }

    public function effectiveRecipient(string $recipient): string
    {
        $recipient = trim($recipient);

        return $this->isRedirecting() ? $this->redirectTo : $recipient;
    }

    public function rewriteSubject(string $subject, string $originalRecipient): string
    {
        $subject = trim($subject);
        if (!$this->isRedirecting()) {
            return $subject;
        }

        return sprintf('[TEST] %s (for %s)', $subject, trim($originalRecipient));
    }

    /** @return array<string, string> */
    public function headers(string $originalRecipient): array
    {
        $originalRecipient = trim($originalRecipient);
        if (!$this->isRedirecting() || '' === $originalRecipient) {
            return [];
        }

        return ['X-Original-Recipient' => $originalRecipient];
    }
}
