# Version inventory

Resolved dependency versions at each release tag, taken from `composer.lock` and `package-lock.json` at that commit. Infrastructure versions come from the Dockerfiles, `compose*.yaml` and `.env` at the same commit. Version ranges declared in manifests are intentionally omitted in favour of the resolved versions that were actually locked.

The older lightweight tag `pre-migration` (`c2e19cd`, 2026-10-24) predates these releases and is kept only for archaeology. It is not a release.

## v0.2.0 — tagged at `be32758` (2026-11-24), current `main` release line

The tag points to the release documentation commit. Lock files are unchanged from the feature tip `3423438`, so the resolved versions below describe both commits.

### Backend and PHP packages

| Component | Resolved version |
|-----------|------------------|
| PHP runtime (container) | 8.2.36 (image `dunglas/frankenphp:1-php8.2`) |
| Symfony framework bundle | 7.4.21 |
| Doctrine ORM | 3.7.5 |
| Doctrine DBAL | 3.10.6 |
| Doctrine bundles (ORM/migrations) | 2.19.2 / 3.7.1 |
| API Platform | 4.4.6 |
| Lexik JWT Authentication Bundle | 3.2.0 |
| Symfony Mailer | 7.4.19 |
| Symfony Security bundle | 7.4.21 |
| Symfony Twig bundle | 7.4.21 |
| Symfony Webpack Encore bundle | 2.4.2 |
| Nelmio CORS bundle | 2.6.1 |
| Symfony Maker bundle | 1.68.0 |
| PHPUnit bridge (PHPUnit run via `simple-phpunit`) | 7.4.21 (PHPUnit 9.6.38) |

### Frontend and Node packages

| Component | Resolved version |
|-----------|------------------|
| Node (build/dev container) | 20 (`node:20-alpine`) |
| Vue | 3.5.46 |
| PrimeVue | 5.0.5 |
| `@primevue/core` | 5.0.5 |
| `@primeuix/themes` | 3.1.1 |
| PrimeIcons | 7.0.0 |
| Sakai Vue source baseline | upstream tag 5.0.0 (`ec6b6ef`), ported to TypeScript/Encore; not an npm package |
| Tailwind CSS / `@tailwindcss/postcss` | 4.3.5 / 4.3.5 |
| `tailwindcss-primeui` | 0.6.1 |
| TypeScript / `vue-tsc` | 5.9.3 / 3.3.14 |
| Webpack Encore / Webpack | 4.7.0 / 5.91.0 |
| Pinia | 2.2.8 |
| vue-router | 4.3.0 |
| Chart.js / `chartjs-plugin-datalabels` | 4.4.2 / 2.2.0 |
| ESLint / `typescript-eslint` / Prettier | 8.57.1 / 8.74.0 / 3.2.5 |
| Sass / PostCSS | 1.75.0 / 8.5.33 |
| `ts-loader` / `vue-loader` | 9.5.1 / 17.3.1 |

### Infrastructure

| Component | Version / pin |
|-----------|---------------|
| FrankenPHP | `dunglas/frankenphp:1-php8.2` (worker mode in prod) |
| Caddy + Mercure hub | bundled with the FrankenPHP image; no separate version pin |
| MariaDB | 11.3.2 (`MARIADB_VERSION`) |
| Mailpit | `axllent/mailpit` — image tag is currently unpinned |
| Node container | `node:20-alpine` |
| Docker Compose | v2 syntax; no version pin in the repository |

## v0.1.0 — frozen baseline (2026-10-26, commit `84550eb`, branch `release/v0.1`)

This is the FrankenPHP/Symfony 7.4 starter before the Sakai 5 / PrimeVue 5 / TypeScript port. It was the tip of `main` until 2026-11-24.

### Backend and PHP packages

| Component | Resolved version |
|-----------|------------------|
| PHP runtime (container) | 8.2.36 (image `dunglas/frankenphp:1-php8.2`) |
| Symfony framework bundle | 7.4.21 |
| Doctrine ORM | 3.7.5 |
| Doctrine DBAL | 3.10.6 |
| Doctrine bundles (ORM/migrations) | 2.19.2 / 3.7.1 |
| API Platform | 4.4.6 |
| Symfony Mailer | 7.4.19 |
| Symfony Security bundle | 7.4.21 |
| Symfony Twig bundle | 7.4.21 |
| Symfony Webpack Encore bundle | 2.4.2 |
| Nelmio CORS bundle | 2.6.1 |
| Symfony Maker bundle | 1.68.0 |
| PHPUnit bridge (PHPUnit run via `simple-phpunit`) | 7.4.21 |

No JWT bundle, refresh-token entities, user entity or mail run/log tables existed in this release.

### Frontend and Node packages

| Component | Resolved version |
|-----------|------------------|
| Node (build/dev container) | 20 (`node:20-alpine`) |
| Vue | 3.4.21 |
| PrimeVue | 3.51.0 |
| PrimeFlex | 3.3.1 |
| PrimeIcons | 7.0.0 |
| TypeScript | 5.4.5 (declared but frontend source was JavaScript; no `vue-tsc`) |
| Webpack Encore / Webpack | 4.6.1 / 5.91.0 |
| vue-router | 4.3.0 |
| Chart.js / `chartjs-plugin-datalabels` | 4.4.2 / 2.2.0 |
| ESLint / `typescript-eslint` / Prettier | 8.57.0 / 7.6.0 / 3.2.5 |
| Sass | 1.75.0 |
| `ts-loader` / `vue-loader` | 9.5.1 / 17.3.1 |

No Tailwind, Pinia, typed API services or frontend test harness existed in this release.

### Infrastructure

Identical pins to v0.2.0: FrankenPHP `1-php8.2` with bundled Caddy/Mercure, MariaDB 11.3.2, unpinned `axllent/mailpit`, Node `20-alpine`.

## Versioning policy

Both releases use semantic versioning. Before 1.0, a minor bump may contain breaking changes; v0.2.0 replaces the entire frontend stack and adds auth/mail endpoints, so it is deliberately a minor rather than a patch bump. Version `1.0.0` is reserved until the residual risks in ADR 0003 are resolved. Branch and tag decisions are recorded in [ADR 0005](../adr/0005-release-and-branch-strategy.md).
