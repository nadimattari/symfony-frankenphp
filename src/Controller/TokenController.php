<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\AuthCookies;
use App\Security\CsrfTokenValidator;
use App\Service\Auth\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Cookie-based refresh and logout; access tokens stay in the response body. */
final class TokenController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenService $refreshTokens,
        private readonly AuthCookies $cookies,
        private readonly CsrfTokenValidator $csrfValidator,
        #[Autowire('%app.access_token_ttl%')]
        private readonly int $accessTokenTtl,
    ) {
    }

    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $rawToken = $request->cookies->get(AuthCookies::REFRESH_NAME);

        if (!is_string($rawToken) || '' === $rawToken) {
            return $this->invalidRefreshToken();
        }
        if (!$this->csrfValidator->isValid($request)) {
            return $this->invalidCsrfToken();
        }

        $rotated = $this->refreshTokens->rotate($rawToken);
        if (null === $rotated) {
            return $this->invalidRefreshToken();
        }

        $user = $rotated['user'];
        $expiresAt = $rotated['expiresAt'];
        $response = new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'expiresIn' => $this->accessTokenTtl,
            'data' => [
                'name' => $user instanceof User ? $user->getName() : $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
        ]);
        $response->headers->setCookie($this->cookies->refresh($rotated['token'], $expiresAt));
        $response->headers->setCookie($this->cookies->csrf(bin2hex(random_bytes(16)), $expiresAt));

        return $response;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $rawToken = $request->cookies->get(AuthCookies::REFRESH_NAME);

        if (is_string($rawToken) && '' !== $rawToken) {
            if (!$this->csrfValidator->isValid($request)) {
                return $this->invalidCsrfToken();
            }
            $this->refreshTokens->revoke($rawToken);
        }

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie($this->cookies->clearRefresh());
        $response->headers->setCookie($this->cookies->clearCsrf());

        return $response;
    }

    private function invalidRefreshToken(): JsonResponse
    {
        $response = $this->json(['message' => 'Invalid or expired refresh token.'], Response::HTTP_UNAUTHORIZED);
        $response->headers->setCookie($this->cookies->clearRefresh());
        $response->headers->setCookie($this->cookies->clearCsrf());

        return $response;
    }

    private function invalidCsrfToken(): JsonResponse
    {
        return $this->json(['message' => 'Invalid or missing CSRF token.'], Response::HTTP_FORBIDDEN);
    }
}
