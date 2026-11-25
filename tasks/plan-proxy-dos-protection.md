# Implementation Plan: Minimal proxy-level DoS posture (`proxy-dos-protection`)

Status: **approved** (2026-11-25) — covered by the approved capability map, spec and up-front plan-gate decision

Source spec: [SPEC-proxy-dos-protection.md](../spec/SPEC-proxy-dos-protection.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Add small, explicitly bounded Caddy protections to both Caddyfiles: a 1 MiB request-body cap and stalled read/write timeouts for non-Mercure requests. Document that this is partial, complementary to Symfony's auth rate limits, and not a WAF, connection limiter or DDoS solution.

## Architecture decisions

- `request_body { max_size 1MB }` applies to the site. JSON login/refresh/logout payloads and the SPA shell are far below this; oversized bodies are rejected with 413 before PHP runs.
- `timeouts` uses the bundled Caddy v2.11.9 directive with `read_timeout 10s`, `write_timeout 30s`, and `max_write_chunk 16KiB`. These are idle-stall deadlines reset on progress, not total request deadlines.
- A `@notMercure` matcher excludes `/.well-known/mercure*` from `timeouts`, because Mercure is a long-lived SSE stream and the docs warn against write-rate limits for SSE.
- `frankenphp list-modules` confirms `http.handlers.request_body`, `http.handlers.timeouts`, `http.handlers.rewrite`, `http.handlers.vars` and `http.matchers.expression`, but no native `rate_limit`/`limit` handler or third-party `caddy-limit` module. No plugin, image, container or external service is added; no request-rate or connection cap is claimed.
- Existing SPA fallback, API handling, worker block, TLS and redirect directives stay unchanged.

## Task list

### Phase 1: Proxy config

- [x] P1 — Add the bounded posture to `docker/caddy/Caddyfile`
- [x] P2 — Apply the same posture to `docker/caddy/Caddyfile.prod`

### Checkpoint: Config valid

- [x] Both Caddyfiles adapt/validate; dev stack reloads cleanly

### Phase 2: Documentation and smoke

- [x] P3 — Add short README security note and dated residual-risk note in ADR 0003
- [x] P4 — Verify oversized body, normal API, SPA deep links, assets, Mercure, 429 contract and dev HTTPS
- [x] P5 — Hygiene and one local commit, no push or tag

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; no push, no tag

## Verification commands

```bash
docker compose exec -T sf-php frankenphp adapt --config /etc/frankenphp/Caddyfile >/dev/null
docker compose exec -T sf-php frankenphp adapt --config /app/docker/caddy/Caddyfile.prod >/dev/null
docker compose exec -T sf-php frankenphp reload --config /etc/frankenphp/Caddyfile
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/auth/login
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8180/api/login
git diff --check
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Write timeout kills Mercure SSE | High | Exclude `/.well-known/mercure*` with `@notMercure`; smoke test the hub |
| Body cap rejects legitimate uploads | Medium | 1 MiB is ample for this JSON API/SPA; verify normal JSON requests still work |
| Documentation overclaims protection | High | State plainly that no rate/connection limiter exists and full DDoS/WAF is out of scope |
| Config syntax unsupported by bundled Caddy | Medium | Source-verify directives and adapt both Caddyfiles before committing |

## Open questions

None.
