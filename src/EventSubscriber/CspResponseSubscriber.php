<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Emits a strict per-response CSP and shares its nonce with Twig through the
 * current Request. No request state is kept on this service, which makes it
 * safe to reuse across FrankenPHP workers.
 */
final class CspResponseSubscriber implements EventSubscriberInterface
{
    private const POLICY = "default-src 'self'; script-src 'self' 'nonce-%s'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'";

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->isMainRequest()) {
            $request->attributes->set('csp_nonce', self::generateNonce());

            return;
        }

        $mainNonce = $this->requestStack->getMainRequest()?->attributes->get('csp_nonce');
        if (is_string($mainNonce)) {
            $request->attributes->set('csp_nonce', $mainNonce);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $nonce = $event->getRequest()->attributes->get('csp_nonce');
        if (!is_string($nonce)) {
            return;
        }

        $event->getResponse()->headers->set('Content-Security-Policy', sprintf(self::POLICY, $nonce));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    private static function generateNonce(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }
}
