# ADR 0001: Keep Webpack Encore (defer Symfony Reprise)

## Status

Accepted (2026-10-26; reaffirmed 2026-11-24)

## Context

Symfony's official front-end documentation describes Webpack Encore as the original bundler integration and now in low-maintenance mode (bug fixes, security patches and `peerDependencies` updates only). It describes Symfony Reprise as the modern, faster alternative for bundler-based projects, built on Vite or Rsbuild rather than Webpack. Reprise generates the same `entrypoints.json`/`manifest.json` contract, so Twig changes are mostly a `encore_entry_*` → `reprise_entry_*` rename.

Current verified requirements (2026-11-24):

- Reprise documentation: PHP 8.4+, Symfony 7.4 or 8, Vite 7/8 or Rsbuild, Node.js 22.18+/24.11+/26+.
- `symfony/reprise` v1.4.0 requires PHP `>=8.4` (Packagist metadata).
- `@symfony/reprise` v1.x requires Node `^22.18.0 || ^24.11.0 || >=26.0` (npm registry metadata).

This project is intentionally pinned to Symfony 7.4 LTS, PHP 8.2.35 (Composer platform), Webpack Encore and a Node 20 container. The user explicitly wants to remain on the Symfony LTS line (7.x) rather than move to Symfony 8 for bundler modernization.

## Decision

Keep **Webpack Encore** for this phase. Do not adopt Vite, Rsbuild or Symfony Reprise.

Track a Reprise migration as a future TODO in `tasks/todo-sakai-primevue-ts.md` (Phase 8) and revisit it only as part of a deliberate platform upgrade.

## Rationale

- Reprise cannot be installed on the current platform without first upgrading PHP 8.2 → 8.4 and Node 20 → 22+/24+.
- Moving to Symfony 8 would leave the LTS line, which is explicitly unwanted.
- Encore remains functional and sufficient; Tailwind 4 can be added through Encore's PostCSS loader.
- A platform upgrade plus bundler swap plus Sakai/PrimeVue migration in one change would multiply risk and delay the frontend work.
- Upstream Sakai 5 is Vite-based, so Reprise would eventually reduce port friction, but that benefit does not justify breaking the agreed stack now.

## Alternatives considered

### Adopt Reprise + Vite now

- **Pros:** official modern replacement; faster builds; native Vite handling of Vue, TypeScript and PostCSS; closer to upstream Sakai.
- **Cons:** requires PHP 8.4 and Node 22+/24+; conflicts with the Symfony 7.4 LTS / PHP 8.2 / Node 20 constraint; adds a platform migration before the UI work.
- **Deferred**, not rejected permanently.

### Use AssetMapper instead of a bundler

- **Pros:** no Node build step; recommended for PHP/Twig projects.
- **Cons:** Symfony's docs note that Vue single-file components cannot be compiled down to browser-executable JS by AssetMapper alone.
- **Rejected** for this Vue SFC frontend.

## Consequences

- Encore stays in low-maintenance mode; security/bug-fix updates only.
- Tailwind 4 must be integrated via `.enablePostCssLoader()` and `postcss.config.cjs`.
- The Sakai 5 port must adapt upstream Vite configuration to Encore (explicit imports, Twig-exposed globals, no `import.meta.env`).
- A future Reprise migration will need its own ADR superseding this one and a separate platform-upgrade plan.

## Revisit

Re-evaluate when all of the following are acceptable together:

- PHP 8.4+ base image and Composer platform,
- Node 22.18+/24.11+ (or 26+) container,
- Symfony 8 or a confirmed Symfony 7.4 LTS-compatible Reprise release,
- Vite 7/8 or Rsbuild,
- and a dedicated migration slice separate from the Sakai/PrimeVue work.

## Sources

- <https://symfony.com/doc/7.4/frontend.html#symfony-reprise> — Reprise as the modern alternative; Encore low-maintenance note
- <https://symfony.com/bundles/reprise/current/index.html#migrating-from-webpack-encore> — migration mapping and feature parity
- <https://symfony.com/bundles/reprise/current/index.html#before-you-start> — PHP 8.4+, Symfony 7.4/8, Vite 7/8, Node 22.18+/24.11+/26+
- <https://repo.packagist.org/p2/symfony/reprise.json> — `symfony/reprise` v1.4.0 requires PHP >=8.4
- <https://registry.npmjs.org/@symfony/reprise/latest> — npm engine requirements
