# Spec: Minimal proxy-level DoS posture (`proxy-dos-protection`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Add a small, honest proxy-level abuse posture to the Caddy files and document its limits. This is **not** a WAF or a full DDoS service, and it does not replace the existing Symfony auth rate limits.

## Source verification

- Bundled runtime is FrankenPHP v1.14.0 / Caddy v2.11.9 (PHP 8.2.36).
- `frankenphp list-modules` includes `http.handlers.request_body`, `http.handlers.timeouts`, `http.handlers.rewrite`, `http.matchers.expression`, `http.handlers.vars` and the FrankenPHP/Mercure handlers.
- It does **not** include a native `rate_limit`/`limit` handler or the third-party `caddy-limit` module. A connection/request-rate limiter therefore cannot be added with bundled directives alone.
- The minimal posture is consequently bounded request bodies and bounded read/write/idle timeouts, plus any simple connection cap confirmed available in the bundled Caddy version. Do not add an unavailable plugin, extra image or external service.

## Commands

```bash
docker compose exec -T sf-php frankenphp validate --config /etc/frankenphp/Caddyfile
docker compose exec -T sf-php frankenphp validate --config /app/docker/caddy/Caddyfile.prod
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/auth/login
git diff --check
```

## Project structure

| Path | Change |
|---|---|
| `docker/caddy/Caddyfile` | Add bounded `request_body` and `timeouts` directives (and a simple connection cap only if verified available) to the dev site; keep the SPA fallback from the previous module working |
| `docker/caddy/Caddyfile.prod` | Apply the same posture to the HTTPS site; keep HTTP→HTTPS redirect, PHP worker and SPA fallback intact |
| `README.md` | Short security note describing the proxy-level limits and stating that they are complementary to, not a replacement for, application rate limiting |
| `docs/adr/0003-auth-architecture.md` | Append a dated note under the open/residual risks recording the partial proxy posture and the reasons no native limiter was added; do not rewrite the original decision or the shipped auth/CSP specs |
| `spec/SPEC-proxy-dos-protection.md`, `tasks/plan-proxy-dos-protection.md`, `tasks/todo-proxy-dos-protection.md` | Dedicated spec/plan/tasks for this module |

No Symfony firewall, rate-limit config, JWT or CSP change. No dependency, Dockerfile or compose change.

## Acceptance criteria

- [ ] Both Caddyfiles load under `frankenphp validate` and the stack reloads without error.
- [ ] Request body size and read/write/idle timeouts are explicitly bounded with values appropriate for a JSON API and SPA shell; chosen values are documented.
- [ ] Deep links, `/api/*`, `/build/*` and Mercure still behave as in the previous module.
- [ ] Login/refresh/logout 429 + `Retry-After` behaviour remains unchanged.
- [ ] Documentation states plainly that proxy-level protection is partial and that a full DDoS/WAF service is out of scope.
- [ ] One local commit containing only this module; no push and no tag.

## Out of scope

Adding Caddy plugins or a new container, changing TLS termination, WAF/CDN integration, application rate-limit thresholds, and any claim that this closes the ADR 0003 DoS risk completely.
