<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Mail\ActiveMailRunException;
use App\Service\Mail\MailRouter;
use App\Service\Mail\MailRunDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Generic mail settings and a single safe test send. No report/mail-run API. */
final class MailController extends AbstractController
{
    public function __construct(
        private readonly MailRouter $router,
        private readonly MailRunDispatcher $dispatcher,
    ) {
    }

    #[Route('/api/mail/settings', name: 'api_mail_settings', methods: ['GET'])]
    public function settings(): JsonResponse
    {
        return $this->json([
            'from' => $this->router->getFrom(),
            'replyTo' => $this->router->getReplyTo(),
            'redirectTo' => $this->router->getRedirectTo(),
            'throttleSeconds' => $this->router->getThrottleSeconds(),
            'isRedirecting' => $this->router->isRedirecting(),
            'testMode' => $this->dispatcher->isTestMode(),
        ]);
    }

    #[Route('/api/mail/test', name: 'api_mail_test', methods: ['POST'])]
    public function test(Request $request): JsonResponse
    {
        if (!$this->dispatcher->isTestMode()) {
            return $this->json([
                'message' => 'Mail test sends are disabled. Set MAIL_REDIRECT_TO or use dev/test.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $recipient = null;
        $content = $request->getContent();
        if ('' !== $content) {
            try {
                $body = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return $this->json(['message' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
            }
            if (!\is_array($body)) {
                return $this->json(['message' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
            }
            if (\array_key_exists('recipient', $body)) {
                if (!\is_string($body['recipient'])) {
                    return $this->json(['message' => 'Invalid recipient address.'], Response::HTTP_BAD_REQUEST);
                }
                $recipient = trim($body['recipient']);
                if (false === filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    return $this->json(['message' => 'Invalid recipient address.'], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        try {
            $run = $this->dispatcher->dispatchTest($recipient);
        } catch (ActiveMailRunException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        } catch (\Throwable) {
            return $this->json(['message' => 'The test mail could not be sent.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'runId' => $run->getId(),
            'status' => $run->getStatus(),
            'recipient' => $run->getRecipient(),
            'effectiveRecipient' => $this->router->effectiveRecipient($run->getRecipient() ?? ''),
            'message' => $run->getMessage(),
        ]);
    }
}
