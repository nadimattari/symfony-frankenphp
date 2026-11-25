# Tasks: Minimal proxy-level DoS posture (`proxy-dos-protection`)

Status: **approved** (2026-11-25) · [plan](plan-proxy-dos-protection.md)

Source spec: [SPEC-proxy-dos-protection.md](../spec/SPEC-proxy-dos-protection.md)

## P1 — Add the bounded posture to the dev Caddyfile

**Description:** Add `@notMercure`, `request_body { max_size 1MB }` and `timeouts @notMercure { read_timeout 10s; write_timeout 30s; max_write_chunk 16KiB }` to the dev site without changing the SPA fallback or handler order.

**Acceptance criteria:**
- [x] Directives are inside the `:80` site block.
- [x] No rate-limit or connection-limit plugin/directive is added.

**Verification:**
- [x] `git diff -- docker/caddy/Caddyfile` shows only the added posture.
- [x] `frankenphp adapt --config /etc/frankenphp/Caddyfile` exits 0.

**Dependencies:** None

**Files likely touched:** `docker/caddy/Caddyfile`

**Estimated scope:** XS

## P2 — Apply the posture to the prod Caddyfile

**Description:** Add the same directives to the HTTPS site in `docker/caddy/Caddyfile.prod`, preserving redirect, TLS, worker and SPA fallback blocks.

**Acceptance criteria:**
- [x] Same values as dev; existing directive blocks unchanged.

**Verification:**
- [x] `frankenphp adapt --config /app/docker/caddy/Caddyfile.prod` exits 0.

**Dependencies:** P1

**Files likely touched:** `docker/caddy/Caddyfile.prod`

**Estimated scope:** XS

### Checkpoint: Config valid

- [x] Both configs adapt/validate; dev reload succeeds

## P3 — Document the partial posture

**Description:** Add a short README note after the application rate-limit paragraph and append a dated residual-risk note to ADR 0003 after the risk table.

**Acceptance criteria:**
- [x] Values, Mercure exclusion and no-native-limiter reason are recorded.
- [x] Text says explicitly that this is partial and not a WAF/DDoS solution.
- [x] Original ADR decision and shipped auth/CSP specs are untouched.

**Verification:**
- [x] `git diff -- README.md docs/adr/0003-auth-architecture.md`

**Dependencies:** P2

**Files likely touched:** `README.md`, `docs/adr/0003-auth-architecture.md`

**Estimated scope:** XS

## P4 — Smoke test routing and limits

**Description:** Verify the new body cap returns 413, normal JSON still reaches Symfony, SPA deep links/assets/Mercure/HTTPS still work, and the 429 contract is unchanged.

**Acceptance criteria:**
- [x] Oversized JSON body → 413.
- [x] Normal JSON request → application response, not 413.
- [x] `/auth/login` and `/pages/crud` → 200 with `#vue-app`.
- [x] `/build/app.js` → 200 with JavaScript content type.
- [x] Mercure hub still reachable.
- [x] Repeated bad logins still produce 429 with `Retry-After`.

**Verification:**
- [x] `curl` against `http://localhost:8180` and `https://localhost:8443`
- [x] Full backend/frontend gates unchanged

**Dependencies:** P3

**Files likely touched:** `tasks/todo-proxy-dos-protection.md` (recorded results only)

**Estimated scope:** M

## P5 — Hygiene and one local commit

**Description:** Stage only this module's files and make one local commit. No push or tag.

**Acceptance criteria:**
- [x] Staged diff contains only the two Caddyfiles, README, ADR and this module's plan/tasks.
- [x] One local commit; clean tree; no push, no new tag.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`

**Dependencies:** P4

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; clean tree; no push, no tag

## Recorded verification results

- Both Caddyfiles add `request_body { max_size 1MB }` and `timeouts @notMercure { read_timeout 10s; write_timeout 30s; max_write_chunk 16KiB }`; `@notMercure` excludes `/.well-known/mercure*` so long-lived SSE is not cut off. Adapted JSON confirms `max_size: 1000000`, `read_timeout: 10s`, `write_timeout: 30s`, `max_write_chunk: 16KiB`, with the matcher present.
- `frankenphp list-modules` confirms `http.handlers.request_body`, `http.handlers.timeouts`, `http.handlers.rewrite`, `http.handlers.vars` and `http.matchers.expression`; no native `rate_limit`/`limit` handler and no `caddy-limit` module. No plugin, image, container or external service was added; no rate or connection cap is claimed.
- `frankenphp adapt` exits 0 for both Caddyfiles. Full `frankenphp validate` against a live Mercure hub cannot run while the dev stack holds `/data/caddy/mercure.db` open; copies without the Mercure block validate `Valid configuration` with exit 0.
- Dev reload after the final config: exit 0. A 1,100,032-byte JSON body to `/api/login` returned **413**; a normal bad-credentials JSON body returned **401** with `{"code":401,"message":"Invalid credentials."}`.
- Routing smoke: `GET /auth/login` and `GET /pages/crud` → 200 with `id="vue-app"`; current Encore entry asset `/build/app.13e2587e.js` → 200 `text/javascript; charset=utf-8`; `GET /.well-known/mercure?topic=proxy-dos` → 200 SSE (curl stopped after 2s as expected); `GET https://localhost:8443/auth/login` → 200.
- Rate-limit contract unchanged: 11 bad logins for a fresh username returned `401` ten times then `429`; the 429 included `Retry-After: 900` and `X-Ratelimit-Limit: 10`.
- README note and dated ADR 0003 residual-risk note added. The ADR note explicitly supersedes the older "proxy-level controls remain out of scope" wording without rewriting the original decision, auth/CSP specs or release docs.
- Full backend gate: `OK (58 tests, 375 assertions)`, exit 0. Frontend gates in `sf-node`: `build`, `test:fetch`, `lint`, `typecheck` all exit 0; lint retains 2586 `prettier/prettier` warnings and 0 errors for the next module.
- `git diff --check` clean; staged secret scan showed no literal credential value; one local commit; no push and no new tag.
