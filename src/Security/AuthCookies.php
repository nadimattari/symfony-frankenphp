<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Cookie helpers for the cookie-authenticated refresh/logout endpoints.
 * The refresh token is httpOnly and scoped to /api; the double-submit CSRF
 * token is readable by the SPA and scoped to / so it is available on any page.
 *
 * Dev/test run over plain HTTP, so `app.cookie_secure` is false there.
 * Production sets it to true.
 */
final class AuthCookies
{
    public const REFRESH_NAME = 'refresh_token';
    public const CSRF_NAME = 'csrf';

    public function __construct(
        #[Autowire('%app.cookie_secure%')]
        private readonly bool $secure,
    ) {
    }

    public function refresh(string $rawToken, \DateTimeImmutable $expiresAt): Cookie
    {
        return new Cookie(
            self::REFRESH_NAME,
            $rawToken,
            $expiresAt,
            '/api',
            null,
            $this->secure,
            true,
            false,
            Cookie::SAMESITE_STRICT,
        );
    }

    public function csrf(string $value, \DateTimeImmutable $expiresAt): Cookie
    {
        return new Cookie(
            self::CSRF_NAME,
            $value,
            $expiresAt,
            '/',
            null,
            $this->secure,
            false,
            false,
            Cookie::SAMESITE_STRICT,
        );
    }

    public function clearRefresh(): Cookie
    {
        return new Cookie(self::REFRESH_NAME, '', 1, '/api', null, $this->secure, true, false, Cookie::SAMESITE_STRICT);
    }

    public function clearCsrf(): Cookie
    {
        return new Cookie(self::CSRF_NAME, '', 1, '/', null, $this->secure, false, false, Cookie::SAMESITE_STRICT);
    }
}
