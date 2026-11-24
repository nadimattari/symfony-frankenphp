# Capability Map: ADR 0003 Hardening toward 1.0.0

Status: **approved** (2026-11-24)

## Objective

Close the two open risks in [ADR 0003](../docs/adr/0003-auth-architecture.md) that block a 1.0.0 release: no Content Security Policy and no throttling on authentication endpoints. Both controls must be testable, opt-out-free in production and documented; neither may change the existing token contract.

## Threat model (short)

| Trust boundary                                                      | Assets                                         | STRIDE concerns                                                                  | Planned control      |
|---------------------------------------------------------------------|------------------------------------------------|----------------------------------------------------------------------------------|----------------------|
| Browser → Symfony `/api/login`, `/api/token/refresh`, `/api/logout` | credentials, refresh cookies, CSRF cookie, JWT | Spoofing by credential stuffing; DoS by unbounded attempts; repudiation of abuse | `auth-rate-limiting` |
| Symfony → browser HTML/JS                                           | JWT in `localStorage`, license key, admin UI   | XSS token theft; tampering via injected scripts                                  | `csp-headers`        |

## Modules

| Module id            | Responsibility                                                                                                                              | Depends on                          | Independently verifiable by                                                                                      |
|----------------------|---------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------|------------------------------------------------------------------------------------------------------------------|
| `auth-rate-limiting` | Throttle login, refresh and logout by client identity; return 429 with `Retry-After`; keep counters in a shared store                       | none                                | PHPUnit functional tests that exhaust each limiter and assert 429/headers, plus config dump                      |
| `csp-headers`        | Emit a strict per-response CSP with a fresh nonce; pass the nonce to the Twig inline globals script; no `'unsafe-inline'` scripts or `eval` | none                                | PHPUnit functional tests asserting header directives/nonce rotation and a browser smoke with zero CSP violations |
| `hardening-release`  | Update ADR 0003 risk register, README/SPEC, version inventory and CHANGELOG; define the 1.0.0 release gate                                  | `auth-rate-limiting`, `csp-headers` | Doc review, full gate run, no secrets in tracked files                                                           |

Build order: **`auth-rate-limiting` → `csp-headers` → `hardening-release`**.

Rate limiting is first because it is the higher-risk change to the auth flow and needs a new Composer dependency; failing fast there avoids building docs around a design that cannot be installed. The two controls have no runtime dependency on each other and could be swapped if the reviewer prefers.

## Proposed defaults (need approval)

- Symfony 7.4 LTS / PHP 8.2 / Lexik JWT 3.2 remain fixed; no Reprise or Symfony 8 work in this initiative (Reprise is targeted for November 2027 per ADR 0001).
- Add `symfony/rate-limiter` and back its state with a **MariaDB/Doctrine DBAL cache pool** so multiple PHP workers share counters; no new service or infrastructure.
- Login: **10 failed attempts per 15 minutes** keyed by client IP + submitted username. Refresh/logout: **30 attempts per 15 minutes** keyed by client IP. Return HTTP 429 with `Retry-After` and generic JSON; never echo credentials.
- CSP: `default-src 'self'`, `script-src 'self' 'nonce-<per-response>'`, `style-src 'self' 'unsafe-inline'` (PrimeVue inline styles), `img-src 'self' data:`, `font-src 'self' data:`, `connect-src 'self'`, `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'none'`, `object-src 'none'`. No `'unsafe-eval'` and no remote allowlists.
- The Twig inline globals script gets `nonce="{{ csp_nonce }}"`; localStorage token storage and the `/api/login` response contract do not change.
- Dedicated task list/plan files under `tasks/`; do not overwrite the existing migration plan, which still has the deliberately open Task 35.

## Out of scope (deferred)

- Moving the access token from `localStorage` to httpOnly cookies (needs a new ADR — ADR 0003 revisit item).
- General API-wide or proxy/Caddy-level DoS protection (Symfony docs explicitly say application rate limiting is not DoS protection).
- Triage of the known dev-toolchain `npm audit` findings (separate release task).
- SPA deep-link host rewrite and the `eraseCredentials()` deprecation.
- Cutting the actual `v1.0.0` tag — this initiative makes the gate green; release/tag is a separate decision.
- Symfony Reprise / Vite (November 2027, ADR 0001).

## Gate

Approve or correct the module boundaries, build order and proposed defaults. After approval, each module gets its own delta spec under `spec/`, then a plan/task breakdown, then TDD implementation.
