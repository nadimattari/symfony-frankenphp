# Tasks: SPA deep-link host rewrite (`spa-deep-links`)

Status: **approved** (2026-11-25) · [plan](plan-spa-deep-links.md)

Source spec: [SPEC-spa-deep-links.md](../spec/SPEC-spa-deep-links.md)

## S1 — Add the SPA fallback to the dev Caddyfile

**Description:** In `docker/caddy/Caddyfile`, add a named `@spa` matcher excluding `/api/*`, `/build/*` and `/.well-known/mercure*`, requiring `not file`, and route matched paths through `rewrite /index.php/` plus `php { env REQUEST_URI /index.php }`. The env override is required because FrankenPHP preserves the original URI for Symfony 7.4 routing.

**Acceptance criteria:**
- [x] Matcher and route are inside the `:80` site block.
- [x] Mercure block, `php_server`, `file_server` and the HTTPS proxy site are unchanged.

**Verification:**
- [x] `git diff -- docker/caddy/Caddyfile` shows only the added fallback.
- [x] `frankenphp adapt --config /etc/frankenphp/Caddyfile` succeeds; validation of a copy without the live Mercure hub succeeds.

**Dependencies:** None

**Files likely touched:** `docker/caddy/Caddyfile`

**Estimated scope:** XS

## S2 — Add the SPA fallback to the prod Caddyfile

**Description:** Apply the same matcher and `route`/`php` override inside the HTTPS site in `docker/caddy/Caddyfile.prod`, leaving the existing `php_server { worker /app/public/index.php 4 }` block untouched.

**Acceptance criteria:**
- [x] Same policy as dev; HTTP→HTTPS redirect, TLS and worker directive unchanged.
- [x] No second worker pool is declared for the SPA route.

**Verification:**
- [x] `frankenphp adapt --config /app/docker/caddy/Caddyfile.prod` succeeds; validation of a copy without the live Mercure hub succeeds.

**Dependencies:** S1

**Files likely touched:** `docker/caddy/Caddyfile.prod`

**Estimated scope:** XS

### Checkpoint: Config valid

- [x] Both configs validate; dev site reloads with no error

## S3 — Smoke test routing classes

**Description:** Verify deep links return the SPA shell and that files, API and Mercure still work.

**Acceptance criteria:**
- [x] `/auth/login` and `/pages/crud` return 200 with `#vue-app` in the body.
- [x] A real `/build/*` asset is served by `file_server`.
- [x] `/api/*` still reaches Symfony (known API path returns its normal JSON response).
- [x] `/.well-known/mercure` still reaches the hub.
- [x] Dev HTTPS (8443) and prod redirect behaviour are unchanged.

**Verification:**
- [x] `curl` status/body checks against `http://localhost:8180` and `https://localhost:8443`
- [x] Existing backend and frontend gates still exit as before

**Dependencies:** S2

**Files likely touched:** `tasks/todo-spa-deep-links.md` (recorded results only)

**Estimated scope:** S

## S4 — Hygiene and one local commit

**Description:** Stage only this module's files and make one local commit. No push or tag.

**Acceptance criteria:**
- [x] Staged diff contains only the two Caddyfiles plus this module's plan/tasks.
- [x] One local commit; clean tree; no push, no new tag.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`

**Dependencies:** S3

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; clean tree; no push, no tag

## Recorded verification results

- Final dev config: `@spa` matcher plus `route @spa { rewrite /index.php/; php { env REQUEST_URI /index.php } }`. A plain `rewrite @spa /index.php` was tested first and failed because Symfony still routed `/auth/login`; the `REQUEST_URI` override is required with the installed FrankenPHP/Symfony versions.
- Final prod config: identical matcher/route in the HTTPS site; `tls internal`, HTTP→HTTPS redirect and `php_server { worker /app/public/index.php 4 }` are byte-for-byte unchanged. The SPA route uses the lower-level `php` handler, so deep links use classic PHP threads while all other PHP requests keep using the worker pool. No second worker pool was declared.
- `frankenphp adapt --config /etc/frankenphp/Caddyfile` → exit 0; `frankenphp adapt --config /app/docker/caddy/Caddyfile.prod` → exit 0.
- Full `frankenphp validate` against either live config exits 1 only because the running dev hub already holds `/data/caddy/mercure.db` open; both configs validate with `Valid configuration` and exit 0 when a copy without the Mercure block is checked. This is an environment lock, not a syntax error.
- Live dev reload after the final edit: exit 0. Smoke results: `GET /auth/login` 200 with `id="vue-app"`; `GET /pages/crud` 200 with `id="vue-app"`; `GET /build/app.js` 200 `text/javascript; charset=utf-8`; `POST /api/login` 400 `application/json` (Symfony reached); `GET /.well-known/mercure?topic=smoke` 200 SSE (curl stopped after 2s as expected); `GET /` 200; `GET https://localhost:8443/auth/login` 200.
- Full backend gate in container: `OK (58 tests, 375 assertions)`, exit 0, no deprecation notices in this run.
- Frontend gates in `sf-node` container: `build`, `test:fetch`, `lint` and `typecheck` all exit 0; lint retains 2586 `prettier/prettier` warnings and 0 errors for the later `prettier-warnings` module.
- Host-side `npm` commands fail with missing `postcss-loader`, `pinia` and `vue-tsc`; that is an incomplete host `node_modules`, not a project regression. Gates were recorded from the project's `sf-node` container.
- `git diff --check` clean; staged secret scan showed no literal credential value; one local commit; no push and no new tag.
