<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;

/** Double-submit check for cookie-authenticated refresh/logout requests. */
final class CsrfTokenValidator
{
    public function isValid(Request $request): bool
    {
        $header = $request->headers->get('X-CSRF-Token');
        $cookie = $request->cookies->get(AuthCookies::CSRF_NAME);

        return is_string($header)
            && '' !== $header
            && is_string($cookie)
            && '' !== $cookie
            && hash_equals($cookie, $header);
    }
}
