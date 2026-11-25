# Changelog

All notable changes to this starter are recorded here, newest first. Versions are annotated tags; resolved dependency versions for each release are in [docs/releases/version-inventory.md](docs/releases/version-inventory.md).

## [v1.0.0] - 2026-11-25

Released 2026-11-25 from `main`. This entry records the security hardening that closes the two blockers in [ADR 0003](docs/adr/0003-auth-architecture.md) and the post-gate cleanup applied before tagging; it makes the promoted stack suitable as a stable starter. See [ADR 0005](docs/adr/0005-release-and-branch-strategy.md) for the branch and tag strategy.

### Added

- Application-level rate limiting on `/api/login`, `/api/token/refresh` and `/api/logout`, returning JSON 429 with `Retry-After`; counters are shared across PHP workers through the MariaDB-backed Doctrine DBAL cache pool.
- A strict per-response `Content-Security-Policy` with a fresh 18-byte nonce applied to the single inline Twig globals script; no `'unsafe-eval'`, remote script allowlist or inline event handlers.
- RED-first functional coverage for the 429 contract and the CSP header/nonce behaviour, plus a real-browser smoke confirming zero CSP violations, local demo assets and no external subresource requests.
- Makefile targets for the everyday frontend commands in `sf-node` (`npm-dev`, `npm-watch`, `npm-build`, `npm-dev-server`, `npm-install`, `npm-ci`, `typecheck`, `lint`, `test:fetch`).

### Changed

- Demo product and avatar images now use local fixtures and the tutorial iframe became an external link, so the strict CSP needs no remote host allowlist.
- ADR 0003 marks the rate-limiting and CSP gaps as mitigated; the remaining accepted and open risks are documented rather than hidden.
- Release documentation describes the 1.0.0 line: resolved versions live in the version inventory, and README/ADR 0005 point at the released annotated tag.
- Caddy dev/prod rewrite extensionless SPA routes to `index.php` for direct deep links, and cap request bodies at 1 MiB with stalled read/write timeouts. The bundled image has no native rate/connection limiter, so proxy-level DoS protection remains partial.
- PHPUnit uses the PHPUnit 9.6 `<coverage>` schema, the `eraseCredentials()` deprecation is attributed, and the full ESLint scope is Prettier-clean without weakening lint rules.

### Removed

- Lamiral-specific `ROLE_CASHIER` / `ROLE_CUSTOMER` constants and the unused `isStaff` / `isCustomer` store getters. The role model is now `ROLE_ADMIN` / `ROLE_USER`.

### Security

- Login throttling allows 10 failed attempts per 15 minutes per IP + username, plus a broader IP-wide counter at 5× that limit; refresh and logout allow 30 attempts per 15 minutes per hashed client IP. This is application-level abuse protection, not DoS protection.
- `script-src 'self' 'nonce-<nonce>'` replaces permissive inline script execution. `style-src 'unsafe-inline'` remains an accepted PrimeVue/Sakai residual, and the access token is still readable by any script that defeats CSP.
- No JWT keys, passphrases, license key values or credentials are tracked; the auth and CSP modules each passed a staged secret scan before merge.
- Dev-toolchain advisories are triaged in [npm-audit-triage.md](docs/security/npm-audit-triage.md): production dependencies report 0 findings; 44 dev/build findings are accepted or deferred without forced upgrades.

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
