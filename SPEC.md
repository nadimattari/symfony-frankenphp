# Spec: FrankenPHP + Symfony 7.4 Migration, Hardening and Sakai/PrimeVue 5 Upgrade

## Objective

Modernize this Symfony + Vue starter template:

1. Replace Apache/PHP container with **FrankenPHP** (Caddy-based) + **Mercure**, with a **separate Node container** for frontend tooling.
2. Make containers **dev / test / prod ready** (profiles, env-specific config, optimized builds). Mailpit is **dev-only**.
3. Upgrade **Symfony 6.4 → 7.4 LTS** (PHP 8.2).
4. Fix configuration bugs/inconsistencies across Symfony, TS, Docker, and tooling.
5. Port upstream **Sakai Vue 5.0.0** on **PrimeVue 5.0.5** using **strict TypeScript** and **Tailwind 4**, keeping Webpack Encore. A Reprise migration is explicitly **deferred** (ADR 0001, Task 35).
6. Add bearer-token authentication and a small, safe mail test flow as reusable starter infrastructure (ADRs 0003 and 0004). Domain-specific Lamiral features (reports, invoices, pricing, Dropbox, portal) stay out of scope.

Primary user: developers using this repo as a starting point. Success = `docker compose up` works in dev, tests pass, prod image builds, Symfony 7.4 boots, and all Sakai routes render with a clean browser console.

## Tech Stack

- PHP 8.2, FrankenPHP (dunglas/frankenphp), **worker mode in prod**
- Symfony 7.4 LTS, Doctrine ORM 3.x, API Platform, Nelmio CORS, Webpack Encore bundle
- Node 20 LTS (separate container)
- MariaDB 11.3, Mailpit (dev profile only), Mercure hub (bundled in FrankenPHP/Caddy)
- Frontend: Vue 3.5.46, PrimeVue 5.0.5, `@primeuix/themes` 3.1.1, PrimeIcons, Tailwind 4.3.5 + `tailwindcss-primeui`, TypeScript 5.9.3 (`strict: true`), ESLint, Prettier
- Tests: PHPUnit via `simple-phpunit`; no frontend framework — a focused `node:test` harness compiles TS into gitignored `var/test-build/`

## Commands

Run frontend commands in `sf-node`; the host does not need Node/npm.

| Task | Command |
|------|---------|
| Start dev stack | `docker compose up -d` |
| Start Mailpit (dev) | `docker compose up -d sf-mailer` |
| Install frontend deps | `docker compose exec -T sf-node npm ci` |
| Frontend dev/watch | `docker compose exec -T sf-node npm run watch` |
| Frontend build | `docker compose exec -T sf-node npm run build` |
| Typecheck | `docker compose exec -T sf-node npm run typecheck` (`vue-tsc --noEmit`) |
| Fetch-service tests | `docker compose exec -T sf-node npm run test:fetch` |
| Lint | `docker compose exec -T sf-node npm run lint` |
| PHPUnit | `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit` |
| Symfony console | `docker compose exec -T sf-php php bin/console ...` |
| Prod image | `docker compose -f compose.yaml -f compose.prod.yaml build sf-php` |

The PrimeUI Community license key is read only from `PRIMEUI_LICENSE_KEY` (gitignored `.env.local` locally, deployment environment elsewhere) and flows env → Symfony parameter → Twig global → `window.PRIMEUI_LICENSE_KEY` → `app.use(PrimeVue, { license })`. Never commit the key; renew it annually (the current key expires 2027-11-14). See README and ADR 0002. JWT passphrases and signing keys are likewise per-environment and gitignored.

## Project Structure

```
/
├── compose.yaml, compose.override.yaml, compose.prod.yaml
├── docker/php, docker/node, docker/caddy
├── .env, .env.test  (+ gitignored .env*.local)
├── config/jwt/      (gitignored, per environment)
├── src/, config/, templates/, assets/, public/, tests/
├── docs/adr/, docs/analysis-lamiral-sakai-upgrade.md
├── tasks/plan-sakai-primevue-ts.md, tasks/todo-sakai-primevue-ts.md
├── SPEC.md, README.md, AGENTS.md
```

## Code Style

- PHP: PSR-12, Symfony conventions, attributes for routing/ORM.
- TS/Vue: strict `vue-tsc`, ESLint flat config, Prettier; every SFC uses `lang="ts"`.
- Keep demo image paths root-absolute (`/demo/images/...`) — history-mode routes rebase relative URLs.

## Testing Strategy

- PHPUnit via `simple-phpunit` (functional/unit coverage for auth and mail).
- `npm run test:fetch`: focused `node:test` coverage of typed fetch/services/guards; no frontend test framework added.
- `npm run typecheck` is the authoritative frontend gate; lint, build and browser smoke supplement it.

## Boundaries

- Always: run tests/build before commit; keep secrets out of git; use env vars.
- Ask first: major dependency upgrades beyond the plan; changing DB engine; migrating bundler.
- Never: commit vendor/node_modules/public/build, JWT keys, passphrases or license keys; edit FrankenPHP base image internals.

## Success Criteria (verified 2026-11-24)

- [x] `docker compose up -d` starts sf-php (FrankenPHP), sf-node, sf-db (and sf-mailer in the dev profile) without Apache.
- [x] No node/npm/nvm in the PHP image.
- [x] Dev/test/prod compose profiles work; Mailpit is dev-only; prod image builds.
- [x] Symfony 7.4 LTS boots (`php bin/console about` reports v7.4.21 on PHP 8.2.36).
- [x] `npm ci && npm run build` succeeds in `sf-node` (one known harmless `postcss-calc` warning).
- [x] `npm run typecheck` uses `vue-tsc --noEmit` with `strict: true` and passes.
- [x] `npm run lint` passes (0 errors; Prettier formatting warnings only); `npm run test:fetch` passes 36/36.
- [x] FrankenPHP worker mode and Mercure hub are enabled in the prod Caddyfile.
- [x] ESLint config works; ChartDataLabels is registered; Makefile is present and corrected.
- [x] License key is env-only and never committed; no license notice appears in the browser.
- [x] No `.js` frontend modules remain; all SFCs use `lang="ts"`; no removed v3 PrimeVue APIs, v3 theme imports or PrimeFlex imports remain.
- [x] Tailwind 4 + `tailwindcss-primeui` generate Sakai utilities; dark mode/cascade layers follow the official guide.
- [x] All Sakai routes render in dev with no console errors or failed resource requests.
- [x] Auth login/refresh/logout and mail test send work in dev (ADRs 0003/0004).
- [x] PHPUnit passes (`OK (50 tests, 254 assertions)`).
- [x] Bundler decision documented: keep Encore; Reprise deferred to a separate platform decision (ADR 0001).
- [x] README, SPEC, ADRs and task docs match the implemented choices.

## Deferred / Resolved

- Mercure: hub enabled; feature usage is optional.
- Reprise: not part of this branch; revisit only after a deliberate PHP 8.4+/Node 22.18+/Symfony LTS decision (Task 35).
- Dev/test are safe for the mail test endpoint; non-dev requires an explicit `MAIL_REDIRECT_TO` (ADR 0004).
