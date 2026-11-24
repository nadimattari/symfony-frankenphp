<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Lexik's failure handler renders authentication exceptions as 401. Replace
 * only throttled-login responses with the shared JSON 429 contract.
 */
final class LoginRateLimitFailureListener implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%app.login_rate_limit_max_attempts%')]
        private readonly int $maxAttempts,
    ) {
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();
        if (!$exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return;
        }

        $minutes = (int) ($exception->getMessageData()['%minutes%'] ?? 1);
        $event->setResponse(new JsonResponse(
            ['message' => 'Too many authentication attempts. Try again later.'],
            Response::HTTP_TOO_MANY_REQUESTS,
            [
                'Retry-After' => (string) max(1, $minutes * 60),
                'X-RateLimit-Limit' => (string) $this->maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ],
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure'];
    }
}
