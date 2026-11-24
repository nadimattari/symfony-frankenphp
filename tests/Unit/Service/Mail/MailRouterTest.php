<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Mail;

use App\Service\Mail\MailRouter;
use PHPUnit\Framework\TestCase;

final class MailRouterTest extends TestCase
{
    public function testWithoutRedirectAddressesAreLeftUntouched(): void
    {
        $router = new MailRouter('  from@example.test  ', ' reply@example.test ', '', 0);

        self::assertSame('from@example.test', $router->getFrom());
        self::assertSame('reply@example.test', $router->getReplyTo());
        self::assertSame('', $router->getRedirectTo());
        self::assertSame(0, $router->getThrottleSeconds());
        self::assertFalse($router->isRedirecting());
        self::assertSame('user@example.test', $router->effectiveRecipient('  user@example.test '));
        self::assertSame('Hello', $router->rewriteSubject('  Hello  ', 'user@example.test'));
        self::assertSame([], $router->headers('user@example.test'));
    }

    public function testRedirectRewritesRecipientSubjectAndAddsOriginalHeader(): void
    {
        $router = new MailRouter('from@example.test', 'reply@example.test', ' redirect@example.test ', 3);

        self::assertTrue($router->isRedirecting());
        self::assertSame('redirect@example.test', $router->getRedirectTo());
        self::assertSame(3, $router->getThrottleSeconds());
        self::assertSame('redirect@example.test', $router->effectiveRecipient('user@example.test'));
        self::assertSame(
            '[TEST] Hello (for user@example.test)',
            $router->rewriteSubject('Hello', 'user@example.test'),
        );
        self::assertSame(
            ['X-Original-Recipient' => 'user@example.test'],
            $router->headers('user@example.test'),
        );
    }

    public function testNegativeThrottleIsClampedToZero(): void
    {
        self::assertSame(0, (new MailRouter('a@example.test', 'b@example.test', '', -5))->getThrottleSeconds());
    }
}
