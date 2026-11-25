# Tasks: CSP headers (`csp-headers` module)

Status: **approved** (2026-11-25); C1–C7 complete locally, push pending — [plan](plan-hardening-csp.md)

Spec: [SPEC-csp-headers.md](../spec/SPEC-csp-headers.md). These tasks are additive; the auth module's `tasks/plan-hardening-1.0.md` / `tasks/todo-hardening-1.0.md` and the Sakai plan with its open Task 35 are not modified.

## C1 — Record approved spec and create the working branch

**Description:** Persist the approved delta spec and branch off local `main` without touching `origin/main`.

**Acceptance criteria:**
- [x] `spec/SPEC-csp-headers.md` is committed with status approved and the remote-media decision recorded.
- [x] `feature/csp-headers` exists from local `main` at `d1ddcab`; no force-push or rewrite.
- [x] No secret value is in the spec (only environment-variable names in code snippets).

**Verification:**
- [x] `git log --oneline -1` → `9f33c2b docs: approve strict nonce-based CSP spec`
- [x] `git status --short --branch`

**Dependencies:** None

**Files likely touched:** `spec/SPEC-csp-headers.md` (committed)

**Estimated scope:** XS

## C2 — RED: add `CspHeadersTest.php` for the 429-free CSP contract

**Description:** Write the functional tests first, before any production change. Assert the exact approved policy, that the rendered inline script carries the same nonce, nonce rotation/entropy, absence of unsafe tokens, and that a JSON response also carries the header.

**Acceptance criteria:**
- [x] `GET /` returns 200 and the response has exactly: `default-src 'self'; script-src 'self' 'nonce-<nonce>'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'` where `<nonce>` is the value found in `<script nonce="...">`.
- [x] `script-src` contains no `'unsafe-inline'`, `'unsafe-eval'`, remote host or `*` source.
- [x] Two consecutive requests produce different nonces; each decodes from base64url to 18 bytes and uses only `[A-Za-z0-9_-]`.
- [x] `GET /api/demo/products` still returns its JSON body and also carries the CSP header.
- [x] The test fails on the unmodified app (RED) before C3.

**Verification:**
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/CspHeadersTest.php` failed before C3 with a missing-header/nonce assertion.
- [x] Commit the RED test before touching production code (`3b6df55`).

**Dependencies:** C1

**Files likely touched:** `tests/Functional/CspHeadersTest.php`

**Estimated scope:** S

## C3 — Emit the nonce-based CSP and nonce the Twig globals script

**Description:** Add a `kernel.request` / `kernel.response` subscriber that generates a per-request nonce and sets the exact policy header; add the matching `nonce` attribute to the only inline script. Keep the policy as a private constant; let existing `autoconfigure` register the subscriber.

**Acceptance criteria:**
- [x] `src/EventSubscriber/CspResponseSubscriber.php` generates the nonce on the main request, copies it to subrequests, and sets the header only on main responses.
- [x] `templates/base.html.twig:15` uses `<script nonce="{{ app.request.attributes.get('csp_nonce') }}">`; no other inline script or inline event handler is added.
- [x] No new Composer/npm dependency, no `config/services.yaml` change, no `'unsafe-eval'` and no remote origin.
- [x] C2 turns GREEN and existing tests remain green.

**Verification:**
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/CspHeadersTest.php` → OK (3 tests, 17 assertions)
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit` → OK (58 tests, 375 assertions)
- [x] `curl -sSI http://localhost:8180/` shows the header; raw document nonce matches the header nonce
- [x] `git diff --check` and a staged secret scan

**Dependencies:** C2

**Files likely touched:** `src/EventSubscriber/CspResponseSubscriber.php`, `templates/base.html.twig`

**Estimated scope:** S

### Checkpoint: CSP core green

- [x] RED test failed before C3 and passes after it
- [x] Full PHPUnit suite green with no regressions
- [x] Header observed on a real HTTP response
- [x] Core control verified by tests and HTTP smoke; final merged-diff review remains in C7

## C4 — Replace remote demo media with local assets

**Description:** Make the demo views render under `default-src 'self'` without adding a remote allowlist. Point product/avatar images at existing local fixtures and replace the YouTube iframe with an external documentation link.

**Acceptance criteria:**
- [x] `List.vue` and `Table.vue` use `/demo/images/product/${item.image}` and `/demo/images/avatar/${...}`; no `https://primefaces.org/cdn/...` reference remains in `assets/`.
- [x] `Documentation.vue` has no remote `<iframe>`; the tutorial is reachable through a normal external anchor.
- [x] No new remote CSP source, no new binary asset, no unused `.video` CSS left behind.
- [x] `grep -R 'https://' assets/ | grep -v '^.*http.*://.*/.*#'` shows no subresource URLs (anchor links are acceptable).

**Verification:**
- [x] `npm run build` succeeds
- [x] `npm run test:fetch`, `npm run lint`, `npm run typecheck` pass
- [x] DevTools Network panel shows no blocked remote subresource on the touched views

**Dependencies:** C3

**Files likely touched:** `assets/views/uikit/List.vue`, `assets/views/uikit/Table.vue`, `assets/views/utilities/Documentation.vue`

**Estimated scope:** M

## C5 — Browser smoke for zero CSP violations

**Description:** Verify the enforced policy in a real browser across the shell, an authenticated flow and a route that loads a dynamic chunk, plus the demo views changed in C4. Confirm the dev-toolbar caveat is understood.

**Acceptance criteria:**
- [x] Zero `Content Security Policy` violation messages in the console on `/`, `/auth/login` (invalid and valid submit), logout, lazy-loaded list/table/documentation routes.
- [x] Demo list/table/documentation views render local images without blocked requests; all `/demo/images/*` responses were 200 and no external subresource was requested.
- [x] In `dev`, the extra nonce and `'unsafe-inline'` are attributable to WebProfilerBundle; the exact enforced policy is asserted by functional tests in `APP_ENV=test` with the toolbar off.

**Verification:**
- [x] Browser smoke via in-app browser tooling: raw document nonce matches the first header nonce (24 chars), no CSP console violations, no failed local asset, and no external resource request. Result recorded here and in the C6 commit message.
- [x] No automated browser framework is added.

**Dependencies:** C4

**Files likely touched:** None (verification only)

**Estimated scope:** XS

### Checkpoint: UI compatible

- [x] Frontend build/lint/typecheck/unit gates pass
- [x] Browser smoke shows zero CSP violations and no blocked local asset
- [x] No remote host appears in any CSP directive

## C6 — Update ADR 0003 and README; run full gates and secret checks

**Description:** Record the verified control, document the policy and its accepted `style-src` residual, and run the final quality gates before merge.

**Acceptance criteria:**
- [x] ADR 0003 risk register marks the XSS/CSP row mitigated with the enforced directives, nonce lifecycle, browser-smoke result and the remaining `localStorage` trade-off; the "no CSP yet" wording is corrected.
- [x] README's authentication section states that a strict nonce CSP is enforced, no inline scripts exist beyond the nonced Twig globals, and `style-src 'unsafe-inline'` is an accepted PrimeVue/Sakai residual.
- [x] Task status files reflect C1–C6 as complete; Task 35 remains open and dated November 2027.
- [x] No secret, JWT passphrase, license key value, raw IP or credential appears in the diff.

**Verification:**
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit` → OK (58 tests, 375 assertions); process exits 1 only for pre-existing indirect deprecations and the `phpunit.xml.dist` schema warning
- [x] `npm run build && npm run test:fetch && npm run lint && npm run typecheck` → exit 0 (lint has 2586 pre-existing Prettier warnings, 0 errors)
- [x] `git diff --check`; staged scan `-i -G'password|secret|api_key|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE'` reviewed for literal values only
- [x] `composer validate --no-check-publish` → valid; strict publish metadata check remains a pre-existing failure

**Dependencies:** C5

**Files likely touched:** `docs/adr/0003-auth-architecture.md`, `README.md`, `tasks/plan-hardening-csp.md`, `tasks/todo-hardening-csp.md`

**Estimated scope:** M

## C7 — Merge the increment into local `main`

**Description:** Fast-forward local `main` with the CSP commits. Do not push and do not rewrite history.

**Acceptance criteria:**
- [x] `main` contains all C1–C6 commits by fast-forward.
- [x] Working tree is clean; no uncommitted production code.
- [x] No tag is cut; `hardening-release` is still pending.

**Verification:**
- [x] `git checkout main && git merge --ff-only feature/csp-headers`
- [x] `git log --oneline --decorate -10 && git status --short --branch`

**Dependencies:** C6

**Files likely touched:** None

**Estimated scope:** XS

### Checkpoint: CSP module merged

- [x] Full gates green, no secret in the diff, no push performed
- [ ] Human review of the merged diff and explicit push decision
- [ ] `hardening-release` (version inventory, CHANGELOG, 1.0.0 gate) starts only after approval
