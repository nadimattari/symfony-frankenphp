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
    - PrimeVue 5
    - PrimeFlex (legacy, being removed)
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
| Lint (`eslint`) | `docker compose exec sf-node npm run lint` |
| Run tests | `docker compose exec -e APP_ENV=test sf-php vendor/bin/simple-phpunit` |

Run the frontend gates in order before committing asset changes:

```bash
docker compose exec sf-node npm ci
docker compose exec sf-node npm run build
docker compose exec sf-node npm run typecheck
docker compose exec sf-node npm run lint
```

`npm run lint` currently reports Prettier formatting warnings but exits successfully with 0 errors.

### PrimeUI license key

PrimeVue 5 needs a PrimeUI license key. Set it only in the gitignored `.env.local` (or as a real environment variable in deployment):

```dotenv
PRIMEUI_LICENSE_KEY=your-key-here
```

The container defines an empty default for this variable, so an unset key does not break tests. The key is exposed to the frontend through Twig and may appear in the built JavaScript bundle; that is expected for PrimeUI licenses. Do not commit it. The held Community key has 4 seats and must be renewed before **2027-11-14**. With an empty key, PrimeUI logs `[PrimeUI] PrimeUI license is not configured.` and displays an `Invalid PrimeUI License` banner. An invalid key shows the same banner; add the real key to `.env.local` to clear it.

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
- FrankenPHP worker mode is enabled in production via `docker/caddy/Caddyfile.prod`.
- Production HTTPS currently uses Caddy's internal CA (`tls internal`). Replace it with a publicly-trusted certificate (or a real domain) before deploying.
