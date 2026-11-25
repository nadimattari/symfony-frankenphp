# Implementation Plan: SPA deep-link host rewrite (`spa-deep-links`)

Status: **approved** (2026-11-25) — covered by the approved capability map, spec and up-front plan-gate decision

Source spec: [SPEC-spa-deep-links.md](../spec/SPEC-spa-deep-links.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Add a Caddy named matcher that rewrites extensionless, non-file SPA history routes to `/index.php`, so direct GETs to `/auth/login` or `/pages/crud` render the Twig shell instead of a 404. Apply the same matcher in dev and prod; leave `/api/*`, `/build/*`, Mercure and existing files alone.

## Architecture decisions

- Use standard Caddy `@spa` named matcher with `not path /api/* /build/* /.well-known/mercure*` and `not file`. Source-verified: empty `file` matcher checks the requested path against site root.
- A plain `rewrite @spa /index.php` is not sufficient with FrankenPHP/Symfony 7.4: FrankenPHP keeps the original `REQUEST_URI`, and `Request::preparePathInfo()` derives the route from it. The working pattern is `route @spa { rewrite /index.php/; php { env REQUEST_URI /index.php } }`, verified live in dev.
- Keep the fallback in each site block rather than a global snippet, so dev TLS proxy and prod redirect blocks stay untouched.
- In prod, the SPA route uses the lower-level `php` handler with the `REQUEST_URI` override; the existing `php_server { worker /app/public/index.php 4 }` block is unchanged and still serves all non-SPA PHP requests. Deep links therefore use classic PHP threads rather than starting a second worker pool.
- Do not change Vue Router, the Twig template or Symfony routes; the shell is already served by the `/` route and mounts at `#vue-app`.
- Verify with `frankenphp validate` on both Caddyfiles and live HTTP smoke tests on the dev stack.

## Task list

### Phase 1: Config

- [x] S1 — Add the SPA fallback matcher/rewrite to `docker/caddy/Caddyfile`
- [x] S2 — Add the identical policy to `docker/caddy/Caddyfile.prod`

### Checkpoint: Config valid

- [x] Both Caddyfiles pass `frankenphp validate`; dev stack reloads cleanly

### Phase 2: Smoke and close

- [x] S3 — Verify deep links, static assets, API, Mercure and dev HTTPS proxy
- [x] S4 — Hygiene and one local commit, no push or tag

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; no push, no tag

## Verification commands

```bash
docker compose exec -T sf-php frankenphp adapt --config /etc/frankenphp/Caddyfile >/dev/null
docker compose exec -T sf-php frankenphp adapt --config /app/docker/caddy/Caddyfile.prod >/dev/null
# validate a copy without the Mercure block; the live dev hub holds /data/caddy/mercure.db open
docker compose exec -T sf-php frankenphp validate --adapter caddyfile --config /tmp/Caddyfile-nomercure
docker compose exec -T sf-php frankenphp validate --adapter caddyfile --config /tmp/Caddyfile.prod-nomercure
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/auth/login
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/pages/crud
curl -sS http://localhost:8180/auth/login | grep -F 'vue-app'
git diff --check
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Rewrite captures API or asset paths | High | Explicit `not path` exclusions plus `not file`; smoke test each class |
| Rewrite alone leaves Symfony on the original path | High | Override `REQUEST_URI` in the SPA route; smoke test deep links and API |
| Prod SPA requests bypass the existing worker | Medium | Keep the worker block unchanged and document that only deep links use classic PHP threads |
| Missing files return the SPA shell instead of 404 | Low | Accepted SPA behaviour; real files are still served directly |

## Open questions

None.
