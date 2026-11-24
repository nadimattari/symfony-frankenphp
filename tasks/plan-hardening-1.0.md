# Implementation Plan: ADR 0003 hardening toward 1.0.0

Status: **approved** (2026-11-24)

Source specs: [capability map](../spec/capability-map.md) · [auth rate limiting spec](../spec/SPEC-auth-rate-limiting.md) · [ADR 0003](../docs/adr/0003-auth-architecture.md)

Tasks are recorded in [todo-hardening-1.0.md](todo-hardening-1.0.md). This is a new plan file; the still-open Task 35 in `tasks/todo-sakai-primevue-ts.md` and the old `tasks/plan.md` are intentionally untouched.

## Overview

Implement the approved hardening initiative in risk-first order: `auth-rate-limiting` → `csp-headers` → `hardening-release`. This plan details the first module only; the later modules get their own delta spec and task expansion after the first module merges. The auth module bounds brute-force traffic on `/api/login`, `/api/token/refresh` and `/api/logout` using Symfony 7.4 rate limiters backed by MariaDB through Doctrine DBAL, and returns a consistent JSON 429 contract without changing the JWT/refresh-cookie contract.

## Architecture decisions

- **Shared counters:** `cache.rate_limiter` is redefined as a Doctrine DBAL pool on the default connection with a fixed `prefix_seed`. The adapter creates `cache_items` on first write when missing; no migration or new service is required.
- **Login:** Symfony's built-in `login_throttling` stays in use with 10 attempts / 15 minutes and `lock_factory: null`. Its accepted trade-off is a separate IP-wide counter at 5× the local limit that survives successful logins.
- **Refresh/logout:** Named fixed-window limiters `auth_refresh` and `auth_logout` (30 / 15 minutes) are consumed in `TokenController`. Keys are `hash_hmac('sha256', $clientIp, APP_SECRET)` truncated to 32 hex characters; raw IPs, tokens and credentials are never stored in limiter keys or messages.
- **Spec correction (needs plan approval):** the approved spec expected Symfony's `TooManyRequestsHttpException` / a `kernel.exception` listener for login 429s. Source inspection of Symfony 7.4.21 and Lexik JWT 3.2 shows the built-in limiter instead throws `TooManyLoginAttemptsAuthenticationException`, which Lexik's configured failure handler converts to a 401 JSON response. To preserve the approved 429 contract, add a small subscriber on `lexik_jwt_authentication.on_authentication_failure` that replaces only that exception's response with the generic 429 JSON plus `Retry-After`. No proxy-level or global exception-rendering changes are made. The minute-granular threshold supplied by Symfony is acceptable for `Retry-After` and will be documented.
- **Increment discipline:** each task is one reviewable commit; tests are RED before the first production change and GREEN before the next slice. Static config/doc-only tasks are verified with `debug:config`, `composer show` and diff review instead of new tests.
- **Branching:** work on a short-lived `feature/auth-rate-limiting` branch from the current local `main` (`fff99fe`). Local `main` is one unpushed docs commit ahead of `origin/main`; no force-push or history rewrite is performed, and pushing is a separate decision at the end.

## Task list

### Phase 1: Auth rate limiting (detailed in this plan)

- [ ] H1 — Record approved specs and create the working branch
- [ ] H2 — RED: add `AuthRateLimitTest.php` for the 429 contract
- [ ] H3 — Install `symfony/rate-limiter`
- [ ] H4 — Configure the shared DBAL pool and named refresh/logout limiters
- [ ] H5 — Enable login throttling and JSON 429 for login
- [ ] H6 — Consume refresh/logout limiters in `TokenController`

### Checkpoint: Auth behaviour complete

- [ ] Focused `AuthRateLimitTest.php` passes
- [ ] Full `simple-phpunit` suite passes with no regressions
- [ ] `debug:config` shows the approved limits and DBAL pool
- [ ] Manual HTTP smoke confirms 429 + `Retry-After` on the 11th login and 31st refresh/logout

### Phase 2: Close the auth module

- [ ] H7 — Update ADR 0003 and README; run secret/full-gate checks
- [ ] H8 — Merge the auth increment back to `main` locally

### Checkpoint: Auth module merged

- [ ] Clean working tree except explicitly identified pre-existing formatting changes
- [ ] No `symfony/lock`, no committed secret, no weakened existing auth test
- [ ] Human review of the merged diff

### Future modules (not actionable in this task list)

- `csp-headers`: write `spec/SPEC-csp-headers.md`, get approval, then expand its own plan/tasks.
- `hardening-release`: update version inventory/CHANGELOG and define the 1.0.0 gate only after both controls are verified.

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Lexik turns login throttling into 401, not 429 | High | Source-verified event subscriber in H5; RED test asserts 429 before production code |
| DBAL cache table missing on first write | Medium | Symfony's DBAL adapter auto-creates `cache_items`; H4 verifies the pool can write/clear in the test DB |
| Coarse `Retry-After` from Symfony's minute-based exception | Low | Document as minute-granular; do not add a custom limiter unless the user asks |
| Successful login leaves the IP-wide counter set | Low | Accepted built-in trade-off; documented in spec and ADR |
| Pre-existing uncommitted markdown table formatting in six files | Low | Inspect before staging; stage only hardening hunks, leave unrelated formatting untouched unless the user approves a separate commit |
| Local `main` ahead of `origin/main` | Low | Keep local history, no rewrite; push only after explicit approval |
| Tests pollute each other through limiter state | Medium | Clear `cache.rate_limiter` in `setUp()` before creating requests, per Symfony docs |

## Verification commands

```bash
docker compose exec -T sf-php composer validate --strict
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/AuthRateLimitTest.php
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
docker compose exec -T sf-php php bin/console debug:config framework rate_limiter
docker compose exec -T sf-php php bin/console debug:config security login_throttling
docker compose exec -T sf-php php bin/console debug:config framework cache
git diff --check
git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only
```

## Resolved decisions

- The source-verified Lexik authentication-failure subscriber correction is approved; it preserves the 429 contract without changing global exception rendering.
- After H8, a human decides whether to push `fff99fe` plus the merged hardening commits to `origin/main`; no push occurs automatically.
