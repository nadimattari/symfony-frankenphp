# Task List: ADR 0003 hardening toward 1.0.0

Plan: [plan-hardening-1.0.md](plan-hardening-1.0.md) · Specs: [capability map](../spec/capability-map.md), [auth rate limiting](../spec/SPEC-auth-rate-limiting.md)

Status: **approved** (2026-11-24)

## Phase 1: Auth rate limiting

### H1 — Record approved specs and create the working branch

**Description:** After plan approval, mark the capability map and auth spec as approved, create `feature/auth-rate-limiting` from the current local `main`, and commit only the approved docs plus the new plan/task files.

**Acceptance criteria:**
- [ ] Both spec status lines say approved with the 2026-11-24 date.
- [ ] Branch is `feature/auth-rate-limiting`; `fff99fe` remains in history with no rewrite.
- [ ] No pre-existing markdown formatting-only change is staged without explicit permission.

**Verification:**
- [ ] `git branch --show-current`
- [ ] `git diff --check`
- [ ] `git status --short`

**Dependencies:** None

**Files likely touched:** `spec/capability-map.md`, `spec/SPEC-auth-rate-limiting.md`, `tasks/plan-hardening-1.0.md`, `tasks/todo-hardening-1.0.md`

**Estimated scope:** S

### H2 — RED: add functional tests for the 429 contract

**Description:** Write `AuthRateLimitTest.php` before any production/config change. Cover login exhaustion, refresh exhaustion, logout exhaustion, generic JSON/no credential echo, `Retry-After`, and a successful login after clearing the limiter pool. Clear `cache.rate_limiter` in `setUp()`.

**Acceptance criteria:**
- [ ] Tests fail against the current code because no 429 is produced.
- [ ] No limiter service is mocked; the real test DB and cache pool are used.
- [ ] Test names describe the observable behaviour.

**Verification:**
- [ ] Focused command fails for the expected missing-behaviour reason: `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/AuthRateLimitTest.php`

**Dependencies:** H1

**Files likely touched:** `tests/Functional/AuthRateLimitTest.php`

**Estimated scope:** S

### H3 — Install `symfony/rate-limiter`

**Description:** Add the missing Composer component and regenerate the lock file without adding `symfony/lock`.

**Acceptance criteria:**
- [ ] `composer.json` and `composer.lock` contain `symfony/rate-limiter`.
- [ ] `symfony/lock` is absent from both files.
- [ ] `composer validate --no-check-publish` passes; the pre-existing missing package `name`/`description` publish metadata is out of scope.

**Verification:**
- [ ] `docker compose exec -T sf-php composer show symfony/rate-limiter`
- [ ] `grep -n 'symfony/lock' composer.json composer.lock` finds nothing
- [ ] Existing auth tests still pass

**Dependencies:** H2

**Files likely touched:** `composer.json`, `composer.lock`, `config/reference.php` (generated config reference)

**Estimated scope:** XS

### H4 — Configure the shared DBAL pool and named limiters

**Description:** Configure `cache.rate_limiter` on Doctrine DBAL with a stable prefix seed, and add `auth_refresh` / `auth_logout` fixed-window limiters at 30 attempts per 15 minutes.

**Acceptance criteria:**
- [ ] `debug:config framework cache` shows the DBAL provider and pool.
- [ ] `debug:config framework rate_limiter` shows both named limiters with 30/15m.
- [ ] The test pool can be cleared and written without a manual migration.

**Verification:**
- [ ] Config dump commands from the plan
- [ ] Focused test still RED only on missing 429 behaviour, not on container errors

**Dependencies:** H3

**Files likely touched:** `config/packages/cache.yaml`, `config/packages/rate_limiter.yaml`

**Estimated scope:** S

### H5 — Enable login throttling and JSON 429 for login

**Description:** Add `login_throttling` to the `main` firewall and a Lexik authentication-failure subscriber that converts only `TooManyLoginAttemptsAuthenticationException` into the approved generic 429 JSON with `Retry-After`/rate-limit headers.

**Acceptance criteria:**
- [ ] `debug:config security firewalls` shows 10 attempts / 15 minutes on `cache.rate_limiter`, `lock_factory: null`.
- [ ] The 11th bad login returns 429 JSON with `Retry-After`; no username/password is echoed.
- [ ] Existing login success and bad-credential 401 behaviour is unchanged before the limit is reached.

**Verification:**
- [ ] Focused login tests pass
- [ ] `AuthFlowTest.php` remains green

**Dependencies:** H4

**Files likely touched:** `config/packages/security.yaml`, `config/services.yaml`, `src/Security/LoginRateLimitFailureListener.php`

**Estimated scope:** S

### H6 — Consume refresh/logout limiters in `TokenController`

**Description:** Inject `#[Target]`ed limiter factories, consume them at the start of refresh/logout with the hashed client IP key, and return the same 429 JSON contract when rejected.

**Acceptance criteria:**
- [ ] 31st refresh and 31st logout requests return 429 + `Retry-After`.
- [ ] Valid refresh/logout still returns 200/204 with cookies unchanged.
- [ ] Invalid cookie/CSRF responses keep their existing 401/403 status before the limit is reached.

**Verification:**
- [ ] Focused `AuthRateLimitTest.php` passes
- [ ] Full `simple-phpunit` suite passes

**Dependencies:** H5

**Files likely touched:** `src/Controller/TokenController.php`

**Estimated scope:** S

## Checkpoint: Auth behaviour complete

- [ ] Focused and full PHPUnit commands pass
- [ ] `debug:config` commands show the approved values
- [ ] Manual HTTP smoke confirms 429 + `Retry-After` on the 11th login and 31st refresh/logout
- [ ] Each H2–H6 slice was committed separately

## Phase 2: Close the auth module

### H7 — Update ADR 0003 and README; run secret/full-gate checks

**Description:** Mark the rate-limit risk mitigated in ADR 0003, document the limits and 429 behaviour in README, update task statuses, and verify no secret or credential appears in the diff. Leave the pre-existing markdown formatting-only changes unstaged unless the user approves them separately.

**Acceptance criteria:**
- [ ] ADR 0003 records 10/15m login, 30/15m refresh/logout, DBAL-backed counters, JSON 429 and accepted trade-offs.
- [ ] README no longer says auth rate limiting is missing.
- [ ] Secret scan reports no staged file containing secret/token/password values.

**Verification:**
- [ ] `git diff --check`
- [ ] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [ ] Full `simple-phpunit` suite passes

**Dependencies:** H6

**Files likely touched:** `docs/adr/0003-auth-architecture.md`, `README.md`, `tasks/todo-hardening-1.0.md`

**Estimated scope:** S

### H8 — Merge the auth increment back to `main` locally

**Description:** Merge the short-lived branch without rewriting history; do not push until the user approves.

**Acceptance criteria:**
- [ ] `main` contains all H1–H7 commits with no force-push.
- [ ] Working tree is clean except explicitly identified pre-existing formatting changes.
- [ ] No uncommitted production code remains.

**Verification:**
- [ ] `git log --oneline --decorate -10`
- [ ] `git status --short --branch`

**Dependencies:** H7

**Files likely touched:** None

**Estimated scope:** XS

## Checkpoint: Auth module merged

- [ ] Review the merged diff with a human
- [ ] Decide explicitly whether to push `fff99fe` plus hardening commits
- [ ] Open the `csp-headers` spec only after this checkpoint is approved
