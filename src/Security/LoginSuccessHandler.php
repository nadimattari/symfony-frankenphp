<?php

namespace App\Security;

use App\Entity\User;
use App\Service\Auth\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Mirrors Lexik's normal success response (including its success event) and
 * adds the rotating httpOnly refresh cookie plus the readable CSRF cookie.
 */
final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly RefreshTokenService $refreshTokens,
        private readonly AuthCookies $cookies,
        #[Autowire('%app.access_token_ttl%')]
        private readonly int $accessTokenTtl,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            throw new \LogicException('The security token does not contain a user.');
        }

        $jwt = $this->jwtManager->create($user);
        $response = new JWTAuthenticationSuccessResponse($jwt);

        $event = new AuthenticationSuccessEvent(['token' => $jwt], $user, $response);
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        $data = $event->getData();
        $data['expiresIn'] = $this->accessTokenTtl;
        $data['data'] = [
            'name' => $user instanceof User ? $user->getName() : $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];
        $response->setData($data);

        if ($user instanceof User && $user->isActive()) {
            $issued = $this->refreshTokens->issue($user);
            $response->headers->setCookie($this->cookies->refresh($issued['token'], $issued['expiresAt']));
            $response->headers->setCookie($this->cookies->csrf(bin2hex(random_bytes(16)), $issued['expiresAt']));
        }

        return $response;
    }
}
