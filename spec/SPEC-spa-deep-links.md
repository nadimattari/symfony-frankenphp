# Spec: SPA deep-link host rewrite (`spa-deep-links`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Make history-mode Vue Router URLs such as `/auth/login` and `/pages/crud` return the SPA shell when requested directly, in both development and production, without breaking real files, API routes or the Mercure hub.

## Current behaviour

- `docker/caddy/Caddyfile` (dev, ports 8180/8443) and `docker/caddy/Caddyfile.prod` (prod, `:443` with `php_server { worker /app/public/index.php 4 }`) both use `root * /app/public`, `encode gzip`, a `mercure` block, `php_server` and `file_server`.
- There is no history fallback, so extensionless SPA paths do not reach the front controller reliably on a direct GET.

## Approach

Add an explicit Caddy matcher and rewrite for extensionless SPA routes:

- A named matcher (e.g. `@spa`) that excludes `/api/*`, `/build/*`, `/.well-known/mercure*` and any path resolving to an existing file, then rewrites the remaining path to `/index.php`.
- Apply the same policy to both Caddyfiles; preserve the dev TLS proxy block and the prod HTTP→HTTPS redirect and PHP worker directive.
- Verify the Caddy matcher syntax against the bundled FrankenPHP/Caddy version before writing the config (FrankenPHP v1.14.0 / Caddy v2.11.9 in the container). Do not guess directive names.

## Commands

```bash
docker compose exec -T sf-php frankenphp validate --config /etc/frankenphp/Caddyfile
docker compose exec -T sf-php frankenphp validate --config /app/docker/caddy/Caddyfile.prod
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/auth/login
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/pages/crud
curl -sS http://localhost:8180/auth/login | grep -F 'vue-app'
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/api/...   # known API path
git diff --check
```

Exact validation paths and smoke URLs will be confirmed in the plan/task step; the acceptance criteria below are authoritative.

## Project structure

| Path | Change |
|---|---|
| `docker/caddy/Caddyfile` | Add SPA fallback matcher/rewrite to the dev site; keep Mercure, `php_server` and `file_server` behaviour intact |
| `docker/caddy/Caddyfile.prod` | Add the same fallback to the HTTPS site; keep the HTTP redirect and worker directive intact |
| `spec/SPEC-spa-deep-links.md`, `tasks/plan-spa-deep-links.md`, `tasks/todo-spa-deep-links.md` | Dedicated spec/plan/tasks for this module |

No application source, Vue Router configuration, manifest or lock file change. This module does **not** add proxy abuse limits — that is the next module, which depends on this one because both touch the Caddyfiles.

## Acceptance criteria

- [ ] Both Caddyfiles load without error under `frankenphp validate`.
- [ ] Direct GETs to `/auth/login` and `/pages/crud` return 200 with the SPA shell (the `#vue-app` mount point is present).
- [ ] Real files under `/build/*` are still served by `file_server` with correct content type; an existing static asset is not rewritten.
- [ ] API routes still reach Symfony via `php_server`; the fallback does not swallow `/api/*`.
- [ ] `/.well-known/mercure` still reaches the Mercure hub.
- [ ] Dev HTTPS proxy and prod HTTP→HTTPS redirect still work.
- [ ] Existing frontend and backend gates still pass; one local commit for this module only, no push and no tag.

## Out of scope

Changing client-side router mode, adding proxy-level DoS/rate limits, changing TLS/proxy headers, and editing the shipped auth/CSP specs.
