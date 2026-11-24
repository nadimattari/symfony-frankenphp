# Changelog

All notable changes to this starter are recorded here, newest first. Versions are annotated tags; resolved dependency versions for each release are in [docs/releases/version-inventory.md](docs/releases/version-inventory.md).

## [v0.2.0] - 2026-11-24

Promoted from `feature/primevue-sakai-ts` to `main`. This release replaces the frontend stack, so it is a breaking (minor) change under the pre-1.0 versioning policy in [ADR 0005](docs/adr/0005-release-and-branch-strategy.md).

### Added

- Upstream Sakai Vue 5.0.0 views adapted to Webpack Encore with PrimeVue 5.0.5 and `@primeuix/themes`.
- Strict TypeScript across all SFCs and modules; `vue-tsc --noEmit` is the authoritative frontend gate.
- Tailwind 4 with `tailwindcss-primeui`, including dark mode and cascade layers.
- Bearer-token authentication: RS256 login, rotating httpOnly refresh cookie, logout and `/api/me` (ADR 0003).
- Generic mail settings and test-send endpoints with a dev/test redirect guard and Mailpit verification (ADR 0004).
- Focused `node:test` harness for typed fetch/services/guards; PHPUnit coverage for auth and mail.
- PrimeUI license key wiring through environment → Symfony parameter → Twig → `app.use(PrimeVue, { license })`.

### Changed

- Vue 3.4 → 3.5.46 and PrimeFlex 3 → Tailwind 4; frontend packages locked to the versions in the inventory.
- Demo data services now use typed local API fixtures instead of mock JavaScript modules.
- Documentation: README commands/setup, SPEC success criteria, ADRs 0002–0005 and a task/plan record of the port.

### Removed

- PrimeFlex, v3-era PrimeVue components/theme files and the deprecated PrimeVue `Chart` component; charts use Chart.js directly.
- Report, invoice, pricing and Dropbox code was deliberately not ported from the reference project.

### Fixed

- Demo image URLs are root-absolute so history-mode routes do not resolve them under route prefixes.
- License key, JWT passphrases and signing keys remain out of tracked files; a tracked-file secret scan is clean.

## [v0.1.0] - 2026-10-26

Frozen at commit `84550eb` on branch `release/v0.1`. The FrankenPHP/Symfony 7.4 starter before the Sakai/PrimeVue 5 port.

### Added

- FrankenPHP (Caddy) with worker mode in prod, bundled Mercure hub and separate Node 20 container.
- Symfony 7.4 LTS, Doctrine ORM, API Platform, Symfony Mailer and Webpack Encore with Sass/TypeScript loaders.
- Docker Compose dev/test/prod profiles with MariaDB 11.3.2 and dev-only Mailpit.
- JavaScript Sakai fork on PrimeVue 3.51 with PrimeFlex 3.3.1.
- PHPUnit smoke test and documented migration/agent configuration.

## Historical tags

- `pre-migration` (`c2e19cd`, 2026-10-24): pre-FrankenPHP, pre-Symfony-7.4 snapshot kept for archaeology; not a release.
