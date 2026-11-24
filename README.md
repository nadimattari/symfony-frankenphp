# Symfony-FrankenPHP

This repository is a starting point for projects using the Symfony framework with FrankenPHP.

## What it contains

- Symfony 7.4.x (LTS)
- Doctrine ORM
- API Platform
- Symfony's Mailer
- Debug & Profiler Bundle
- Maker Bundle
- WebPack Encore
    - Sass/SCSS loader
    - Tailwind 4 via PostCSS
    - TypeScript via `ts-loader`
    - Vue loader
- VueJS 3.5 (required by PrimeVue 5)
    - PrimeVue 5 (Community license key required)
    - PrimeIcons
    - FontAwesome
    - MaterialIcons
    - Chart.js
    - ChartDataLabels
- SakaiVue templates
- ESLint, Prettier, SASS, etc
- Docker
    - MariaDB 11.3.2
    - FrankenPHP (Caddy) + Mercure
    - Node 20 (separate container)
    - Mailer - Mailpit (dev only)

## Commands

Run frontend commands in `sf-node` (Node 20); the host does not need Node or npm installed.

| Task | Command |
|------|---------|
| Start/build containers (dev) | `docker compose up -d` |
| Stop containers | `docker compose down` |
| Production stack | `docker compose -f compose.yaml -f compose.prod.yaml up -d --build` |
| Install frontend dependencies | `docker compose exec sf-node npm ci` |
| Compile JS/CSS (dev) | `docker compose exec sf-node npm run dev` |
| Watch JS/CSS | `docker compose exec sf-node npm run watch` |
| Production asset build | `docker compose exec sf-node npm run build` |
| Typecheck (`vue-tsc --noEmit`) | `docker compose exec sf-node npm run typecheck` |
| Frontend service/guard tests | `docker compose exec sf-node npm run test:fetch` |
| Lint (`eslint`) | `docker compose exec sf-node npm run lint` |
| Run tests | `docker compose exec -e APP_ENV=test sf-php vendor/bin/simple-phpunit` |

Run the frontend gates in order before committing asset changes:

```bash
docker compose exec sf-node npm ci
docker compose exec sf-node npm run test:fetch
docker compose exec sf-node npm run typecheck
docker compose exec sf-node npm run build
docker compose exec sf-node npm run lint
```

`npm run lint` currently reports Prettier formatting warnings but exits successfully with 0 errors.

### PrimeUI license key

PrimeVue 5 needs a PrimeUI license key. Set it only in the gitignored `.env.local` (or as a real environment variable in deployment):

```dotenv
PRIMEUI_LICENSE_KEY=your-key-here
```

The container defines an empty default for this variable, so an unset key does not break tests. The key is exposed to the frontend through Twig and may appear in the built JavaScript bundle; that is expected for PrimeUI licenses. Do not commit it. The held Community key has 4 seats and must be renewed before **2027-11-14**. With an empty key, PrimeUI logs `[PrimeUI] PrimeUI license is not configured.` and displays an `Invalid PrimeUI License` banner. An invalid key shows the same banner; add the real key to `.env.local` to clear it.

### Authentication

The SPA uses a short-lived RS256 access token in `localStorage` and an opaque refresh token in an httpOnly cookie. Endpoints:

| Endpoint | Purpose |
|----------|---------|
| `POST /api/login` | JSON `{username, password}` → `{token, expiresIn, data:{name, roles}}`, sets refresh + CSRF cookies |
| `POST /api/token/refresh` | Requires the refresh cookie and `X-CSRF-Token`; rotates both cookies and returns a new token |
| `POST /api/logout` | Revokes the refresh token, clears cookies, returns 204 |
| `GET /api/me` | Bearer-authenticated example endpoint |

Set up keys per environment (never commit them):

```bash
docker compose exec -T sf-php php bin/console lexik:jwt:generate-keypair
# put the passphrase in gitignored .env.local (JWT_PASSPHRASE=...)
# CI/tests likewise need gitignored .env.test.local or an equivalent env var
```

`ACCESS_TOKEN_TTL` defaults to 3600 seconds. API Platform docs are registered only in `dev`; other `/api` routes require `ROLE_USER`, except the intentionally public `/api/demo/*` fixtures. The dev server only serves `/`, so SPA routes such as `/auth/login` and `/pages/crud` work through client-side navigation; a real deployment needs a host rewrite to `index.php` for direct links.

The accepted XSS trade-off, verification results and residual risks (no CSP yet, no auth rate limiting, non-revocable access tokens, dev dependency advisories) are recorded in [ADR 0003](docs/adr/0003-auth-architecture.md). Do not expose this authentication setup to untrusted content until those items are resolved.

### Mail

Mail settings come from environment variables. Put real values in the gitignored `.env.local` (or the deployment environment), never in tracked files.

| Variable | Default | Purpose |
|----------|---------|---------|
| `MAILER_DSN` | `smtp://sf-mailer:1025` (`.env`), `null://null` (`.env.test`) | Transport; override in real deployments |
| `MAIL_FROM` | empty | From address; the dispatcher falls back to `no-reply@example.test` |
| `MAIL_REPLY_TO` | empty | Optional `Reply-To` header |
| `MAIL_REDIRECT_TO` | empty | When set, all messages go here, with `[TEST] ... (for <original>)` subject and `X-Original-Recipient` header |
| `MAIL_THROTTLE_SECONDS` | `0` | Integer pause between consecutive messages |

Two bearer-authenticated endpoints are available:

| Endpoint | Purpose |
|----------|---------|
| `GET /api/mail/settings` | `{from, replyTo, redirectTo, throttleSeconds, isRedirecting, testMode}`; drives the `TEST MODE` banner |
| `POST /api/mail/test` | Optional `{recipient}`; sends one message and returns `{runId, status, recipient, effectiveRecipient, message}` |

To verify delivery in dev (start Mailpit with `docker compose up -d sf-mailer` if the `dev` profile service is not running):

```bash
TOKEN=$(curl -sS -X POST http://localhost:8180/api/login \
  -H 'Content-Type: application/json' \
  -d '{"username":"<dev-user>","password":"<dev-password>"}' | jq -r .token)
curl -sS -H "Authorization: Bearer $TOKEN" http://localhost:8180/api/mail/settings
curl -sS -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"recipient":"someone@example.test"}' http://localhost:8180/api/mail/test
```

Open http://localhost:8125 to see the captured message. With no redirect configured the recipient is used as given; omitting it sends to `test@example.test`. Dev uses Mailpit and `test` uses the null transport, so both are safe without a redirect. In any other environment `/api/mail/test` returns `503` unless `MAIL_REDIRECT_TO` is set; enable it there only with a real `MAILER_DSN`, `MAIL_FROM` and a dedicated redirect inbox. Sends are synchronous — there is no detached worker. Report, invoice and pricing mail endpoints are intentionally not ported; see [ADR 0004](docs/adr/0004-mail-architecture.md).

## Releases

`main` is the current release line: **v0.2.0** (PrimeVue 5 / Sakai 5 / TypeScript / Tailwind 4 plus auth and mail). The previous starter baseline is frozen on branch `release/v0.1` and tagged **v0.1.0** (commit `84550eb`); it is not receiving further changes. The older `pre-migration` tag (`c2e19cd`) is historical only.

- [CHANGELOG.md](CHANGELOG.md) — human-readable release notes.
- [docs/releases/version-inventory.md](docs/releases/version-inventory.md) — resolved framework, library and infrastructure versions for each release.
- [ADR 0005](docs/adr/0005-release-and-branch-strategy.md) — why the baseline is a frozen branch and why `main` was fast-forwarded instead of rewritten.

## Services

### Development

| Service     | Host URL / port          | Container port |
|-------------|--------------------------|----------------|
| `sf-php`    | http://localhost:8180    | 80             |
| `sf-php`    | https://localhost:8443   | 443            |
| `sf-db`     | localhost:3381           | 3306           |
| `sf-mailer` | http://localhost:8125    | 8025           |
| `sf-node`   | -                        | -              |

Dev HTTPS uses Caddy's internal CA, so the certificate is not publicly trusted (use `curl -k` or accept the browser warning).

### Production

| Service  | Host URL / port         | Container port |
|----------|-------------------------|----------------|
| `sf-php` | http://localhost (80)   | 80             |
| `sf-php` | https://localhost (443) | 443            |

Production exposes **only** ports 80 and 443. The database and Mailpit are not published; Mailpit is not started at all in production. `sf-node` is only run through the `build` profile to compile assets.

## Notes

- Webpack Encore is in maintenance mode. Symfony Reprise is the modern replacement but requires PHP 8.4+, Node 22.18+/24.11+ and Symfony 7.4 or 8; this project stays on Symfony 7.4 LTS / PHP 8.2 / Node 20 for now. Re-evaluation is tracked in ADR 0001 and Task 35.
- Authentication decisions, verification results and residual risks are in ADR 0003 (`docs/adr/0003-auth-architecture.md`).
- FrankenPHP worker mode is enabled in production via `docker/caddy/Caddyfile.prod`.
- Production HTTPS currently uses Caddy's internal CA (`tls internal`). Replace it with a publicly-trusted certificate (or a real domain) before deploying.
