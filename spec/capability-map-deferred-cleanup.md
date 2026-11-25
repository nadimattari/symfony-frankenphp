# Capability Map: Deferred cleanup after 1.0.0

Status: **approved** (2026-11-25)

Source: [hardening release spec](SPEC-hardening-release.md) · [README releases](../README.md) · [ADR 0003](../docs/adr/0003-auth-architecture.md)

## Objective

Close the remaining deferred/open items from the prepared 1.0.0 gate as six bounded, independently verifiable modules, one commit per module. These are follow-up changes to shipped configuration and documentation; each module will get its own small spec before implementation. Task 35 / Symfony Reprise stays pending until November 2027 and is not part of this initiative.

## Modules

| Module id              | Responsibility                                                                                                     | Depends on            | Independently verifiable by                                                                                             |
|------------------------|--------------------------------------------------------------------------------------------------------------------|-----------------------|-------------------------------------------------------------------------------------------------------------------------|
| `phpunit-schema`       | Remove the `phpunit.xml.dist` configuration-schema warning without weakening coverage or test settings             | none                  | `simple-phpunit` prints `OK (58 tests, 375 assertions)` with no “configuration did not pass validation” warning         |
| `erase-credentials`    | Resolve the Symfony 7.3+ `User::eraseCredentials()` deprecation without changing the auth contract                 | none                  | Full PHPUnit suite green; deprecation count for `eraseCredentials()` drops to zero; login/refresh/logout smoke still OK |
| `spa-deep-links`       | Rewrite history-mode SPA routes to `index.php` in dev and prod Caddy config without breaking files, API or Mercure | none                  | Direct HTTP GETs to `/auth/login` and `/pages/crud` return the SPA shell; `/api/*`, `/build/*` and Mercure still work   |
| `proxy-dos-protection` | Add a minimal Caddy/proxy-level abuse posture and document its limits                                              | `spa-deep-links`      | Caddy config validates/reloads; bounded request behaviour verified by HTTP smoke; README/ADR note the residual risk     |
| `prettier-warnings`    | Clear the 2586 `prettier/prettier` warnings by formatting the existing lint scope, without weakening lint rules    | none (scheduled late) | `npm run lint` exits 0 with 0 warnings and 0 errors; build/typecheck/fetch gates still exit 0                           |
| `npm-audit-triage`     | Record a reachability-based triage of the 44 dev-toolchain findings and an explicit accept/defer decision          | none (scheduled late) | Triage document lists counts, severity, affected tools, reachability rationale and follow-up owner/date; no lock churn  |

Build order: **`phpunit-schema` → `erase-credentials` → `spa-deep-links` → `proxy-dos-protection` → `prettier-warnings` → `npm-audit-triage`**.

The two PHP hygiene modules are first because they are small and fail fast. `spa-deep-links` and `proxy-dos-protection` touch the same Caddyfiles, so they are sequential by design rather than parallel. `prettier-warnings` and `npm-audit-triage` are independent but scheduled last so the final gate run and release docs reflect their settled state.

## Proposed defaults (need approval)

- One local commit per module, with a descriptive `fix:`, `chore:` or `docs:` message as appropriate. No push and no tag without explicit approval.
- No change to the shipped platform: Symfony 7.4 LTS / PHP 8.2 / Node 20 remain fixed.
- `phpunit-schema`: adjust only `phpunit.xml.dist` to the schema shipped with PHPUnit 9.6.38. No test removal, assertion change or new coverage threshold.
- `erase-credentials`: source-check Symfony 7.4 `UserInterface`, then remove the empty method or apply the exact deprecation attribute Symfony expects. No token, cookie or firewall contract change.
- `spa-deep-links`: add a Caddy front-controller fallback for extensionless SPA routes; do not capture real files, `/api/*`, or the Mercure hub. Apply to both `docker/caddy/Caddyfile` and `docker/caddy/Caddyfile.prod`.
- `proxy-dos-protection`: use only directives supported by the bundled FrankenPHP/Caddy image. If a native rate limiter is unavailable, implement bounded request/body/timeout settings and document that proxy-level DoS protection remains partial. Do not weaken or duplicate the existing Symfony auth rate limits.
- `prettier-warnings`: run the repository’s existing formatter through ESLint (`--fix`) across the current lint scope; keep `prettier/prettier` as a warning and do not disable the rule to manufacture a clean result.
- `npm-audit-triage`: `npm audit --omit=dev` currently reports 0 vulnerabilities and the full toolchain reports 44 (3 critical, 20 high, 16 moderate, 5 low). Record findings by tool/reachability and decide per item whether to accept, defer, or schedule an upgrade; no forced upgrades in this initiative.
- Dedicated spec/plan/task files under `spec/` and `tasks/`; never overwrite the shipped `spec/capability-map.md`, auth/CSP specs, `tasks/todo-sakai-primevue-ts.md`, or its deliberately open Task 35.

## Out of scope (deferred)

- Task 35: Symfony Reprise / Vite re-evaluation — November 2027, Symfony 8.4 LTS.
- Creating or pushing `v1.0.0`, or rewriting shipped release docs to claim these fixes existed at tag time.
- Forced npm dependency upgrades, lock-file churn, or a new frontend test framework.
- A full WAF/cdn/DDoS service, TLS vendor changes, or infrastructure outside the two Caddyfiles.
- Changes to the JWT, refresh-cookie, CSP or application rate-limit contracts.

## Gate

Approve or correct the module boundaries, dependency direction, build order and proposed defaults. After approval, each module gets its own small spec through the same Specify → Plan → Tasks → Implement cycle.
