# Spec Delta: Auth rate limiting — V1

Status: **approved** (2026-11-24)

Parent: [SPEC.md](../SPEC.md) · Capability map: [capability-map.md](capability-map.md) · Risk source: [ADR 0003](../docs/adr/0003-auth-architecture.md) §Verification and residual risk, row "No rate limiting on `/api/login` or refresh"

## Objective

Bound brute-force and refresh-abuse traffic on the three unauthenticated auth endpoints without changing the token contract. A client that repeatedly submits bad credentials or hammers refresh/logout receives HTTP 429 with a usable `Retry-After`; ordinary login, refresh and logout continue to work exactly as before.

## Tech stack

Symfony 7.4.21 / PHP 8.2, Lexik JWT Authentication Bundle 3.2.0, `symfony/rate-limiter` (new locked dependency), MariaDB 11.3.2 via Doctrine DBAL as the shared counter store. No `symfony/lock` dependency is added; limiters use `lock_factory: null`.

## Commands

```bash
# Add the component (once, committed with composer.lock)
docker compose exec -T sf-php composer require symfony/rate-limiter

# Inspect the effective configuration
docker compose exec -T sf-php php bin/console debug:config framework rate_limiter
docker compose exec -T sf-php php bin/console debug:config security login_throttling

# Focused and full backend tests
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/AuthRateLimitTest.php
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
```

No frontend commands are needed for this module.

## Project structure

| Path                                     | Change                                                                                         |
|------------------------------------------|------------------------------------------------------------------------------------------------|
| `composer.json`, `composer.lock`         | Add `symfony/rate-limiter`                                                                     |
| `config/packages/cache.yaml`             | Configure the shared `cache.rate_limiter` DBAL pool and a stable `prefix_seed`                 |
| `config/packages/rate_limiter.yaml`      | Add `auth_refresh` and `auth_logout` fixed-window limiters (30 / 15 minutes)                   |
| `config/packages/security.yaml`          | Add `login_throttling` to the `main` firewall (10 / 15 minutes, shared pool)                   |
| `src/Controller/TokenController.php`     | Consume the two named limiters and return 429 JSON with `Retry-After`                          |
| `tests/Functional/AuthRateLimitTest.php` | New RED-first functional coverage; clear the limiter pool in `setUp()`                         |
| `docs/adr/0003-auth-architecture.md`     | Mark the rate-limit row mitigated after verification (CSP row stays open until its own module) |
| `README.md`                              | Document the new limits and 429 behaviour                                                      |

## Code style

Follow the existing PHP style: strict types are not currently declared in controllers, so do not introduce them here. Use constructor injection with Symfony 7.4 `#[Target]` attributes and explicit return types. Example of the intended injection pattern:

```php
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

public function __construct(
    #[Target('auth_refresh')] private readonly RateLimiterFactoryInterface $refreshLimiter,
    #[Target('auth_logout')] private readonly RateLimiterFactoryInterface $logoutLimiter,
) {
}
```

Named limiter targets drop the `.limiter` suffix in Symfony 7.4. Return JSON errors from `/api` controllers; never echo the submitted username, password or token in a rate-limit response.

### Sources for this pattern

- <https://symfony.com/doc/7.4/rate_limiter.html#injecting-the-rate-limiter-service> — `RateLimiterFactoryInterface` and `#[Target]` injection
- <https://symfony.com/doc/7.4/rate_limiter.html#exposing-the-rate-limiter-status> — `Retry-After`, `X-RateLimit-Limit` and `X-RateLimit-Remaining`
- <https://symfony.com/doc/7.4/security.html#limiting-login-attempts> — `login_throttling` and `composer require symfony/rate-limiter`
- <https://symfony.com/doc/7.4/reference/configuration/security.html#login-throttling> — `max_attempts`, `interval`, `cache_pool`, `lock_factory`

## New behaviour

1. **Shared store.** `cache.rate_limiter` uses `cache.adapter.doctrine_dbal` with `default_doctrine_dbal_provider: doctrine.dbal.default_connection`. Set a fixed `framework.cache.prefix_seed` for `symfonyfrankenphp` so counters survive deployments and are shared by workers; the DBAL adapter stores items in MariaDB (Symfony 7.4 supports MariaDB 10.2+). No new service or environment variable.
2. **Login.** `security.firewalls.main.login_throttling` uses `max_attempts: 10`, `interval: '15 minutes'`, `cache_pool: cache.rate_limiter`, `lock_factory: null`. Symfony's default limiter tracks username+IP (local) and IP (global, 5× the local limit), and resets only the local counter on success. This is accepted: it protects both credential stuffing and username spraying.
3. **Refresh and logout.** Two separate fixed-window limiters `auth_refresh` and `auth_logout`, each `limit: 30`, `interval: '15 minutes'`, shared pool, no lock. Counter keys are `hash_hmac('sha256', $clientIp, APP_SECRET)` truncated to 32 hex chars — never the raw IP or token. Each limiter is consumed at the start of the matching controller action; an invalid cookie still consumes a token.
4. **429 contract.** Limit responses are JSON `{"message":"Too many authentication attempts. Try again later."}` with `Retry-After` (seconds) and `X-RateLimit-Limit` / `X-RateLimit-Remaining`. Source inspection of Symfony 7.4.21 and Lexik JWT 3.2 showed that built-in login throttling throws `TooManyLoginAttemptsAuthenticationException`, which Lexik's configured failure handler renders as 401 JSON. Therefore a subscriber on `lexik_jwt_authentication.on_authentication_failure` replaces only that exception's response with the generic 429 JSON. Symfony supplies a minute-granular threshold, so login `Retry-After` is minute-granular; refresh/logout use exact seconds. No `kernel.exception` listener or other exception rendering change is needed.
5. **Test isolation.** Functional tests clear `cache.rate_limiter` in `setUp()` exactly as the Symfony rate-limiter docs recommend; existing auth-flow tests continue to pass with the higher limits.

## Testing strategy

- RED first: write `AuthRateLimitTest.php` before changing config or controllers.
- Small/medium functional tests with the real DBAL pool and the existing test database; no mocking of the limiter.
- Cases: 11th bad login → 429 + `Retry-After`; successful login still returns JWT and cookies; 31st refresh attempt → 429; 31st logout attempt → 429; `cache.rate_limiter` resolves to `DoctrineDbalAdapter`; generic error body contains no credentials or token.
- Full gate: the README's `simple-phpunit` command must report no regressions. Frontend gates are untouched.

## Boundaries

- **Always:** clear the rate-limiter pool in tests; use generic 429 messages; keep counters in the shared DBAL pool; run the native audit before release (`composer audit` / `npm audit` as documented).
- **Ask first:** changing thresholds, adding `symfony/lock` or another backend, applying different limits per environment, or extending rate limiting beyond `/api/login`, `/api/token/refresh` and `/api/logout`.
- **Never:** commit secrets, log passwords/tokens/raw IPs in rate-limit keys or messages, disable throttling in production, or weaken existing auth checks to make tests pass.

## Success criteria

- [ ] `symfony/rate-limiter` is in `composer.json` and `composer.lock`; no `symfony/lock` dependency.
- [ ] `debug:config` shows login 10/15m and both custom limiters 30/15m on the DBAL-backed pool.
- [ ] New functional tests fail before the fix and pass afterwards; full `simple-phpunit` suite is green.
- [ ] Manual HTTP check: 11th bad login and 31st refresh/logout return 429 with `Retry-After`; a valid login immediately after clearing the pool still succeeds.
- [ ] ADR 0003 rate-limit row and README document the limits; no secret or raw credential appears in the diff.

## Out of scope (deferred)

- CSP (separate `csp-headers` module).
- Proxy/Caddy-level DoS protection — Symfony docs explicitly state application rate limiting is not DoS protection.
- Global API rate limiting, per-user quotas, CAPTCHA, MFA and account lockout emails.
- Changing the approved thresholds or adding a Redis service.
- Cutting the `v1.0.0` tag.

## Open questions

None. Accepted decision: Symfony's built-in login limiter intentionally retains its IP-wide counter after a successful single-user login; this is the trade-off for built-in brute-force protection and is documented rather than overridden.
