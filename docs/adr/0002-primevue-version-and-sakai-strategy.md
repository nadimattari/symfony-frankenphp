# ADR 0002: PrimeVue version and Sakai upgrade strategy

## Status

Accepted (2026-11-24)

## Context

The starter currently uses:

- PrimeVue **3.51.0** and PrimeFlex **3.3.1**
- Webpack Encore **4.6.1** (keep decision recorded in `docs/adr/0001-bundler.md`)
- A Sakai Vue-derived frontend ported to Encore, written in JavaScript
- Vue **3.4.21**, TypeScript **5.4.5**, but no `.ts` files and no `vue-tsc`

We want to:

1. Upgrade PrimeVue (versions 4 and 5 are available to us).
2. Upgrade the Sakai Vue template to current upstream.
3. Convert the frontend from JavaScript to TypeScript.
4. Reuse suitable infrastructure from the Lamiral project, plus its auth and mail layers.

Relevant upstream facts (verified against official sources):

- PrimeVue 4.5.4 is MIT and is what upstream **Sakai Vue 5.0.0** uses.
- PrimeVue 5.0.5 is the current release. The official v5 migration guide states there are **no breaking changes to public component APIs** from v4.
- The **PrimeUI Community License** includes the full core libraries (PrimeVue, PrimeReact, PrimeNG, PrimeIcons) — not a stripped-down version. It excludes PrimeUI PRO components (Scheduler, Charts, Task Board, Text Editor, Diagram), PrimeBlocks, Theme Designer, and premium support.
- Community licenses are per developer (max 4 seats), valid 12 months, renewable free while eligibility continues, and verified offline with no telemetry.
- v3-era component names were removed in v4/v5 and must all be migrated.
- PrimeVue 5.0.5 declares a peer dependency on Vue `^3.5.0`, so the previous Vue 3.4 baseline cannot be retained with the accepted v5 target.
- Upstream Sakai 5.0.0 is still JavaScript and is built with Vite + Tailwind 4, not Encore.

Full analysis: `docs/analysis-lamiral-sakai-upgrade.md`.

## Decision

1. Target **PrimeVue 5.0.5 directly** with the held **PrimeUI Community License**. Do not perform an intermediate v4 release.
2. Use **Tailwind CSS 4 + `tailwindcss-primeui` + `@primeuix/themes`** for styling. **Remove PrimeFlex** (v3 is incompatible with PrimeVue 4+, and Tailwind replaces it in the upstream Sakai styling).
3. Supply the license key via a build/runtime environment value (`PRIMEUI_LICENSE_KEY`), injected through Twig into `assets/app.ts`; never commit the key to this public repository. The official config option is `app.use(PrimeVue, { license: '...' })`.
4. Port **upstream Sakai Vue tag 5.0.0** (commit `ec6b6ef...`) onto the existing Encore setup, using TypeScript for all ported/new files. Keep a pristine upstream reference for the pinned tag; local TS/Encore/Tailwind adaptations are the fork.
5. Keep **Webpack Encore** and add Tailwind 4 via PostCSS (`.enablePostCssLoader()` + `@tailwindcss/postcss`). Do not adopt Vite.
6. Convert **all SFCs and all JS modules to TypeScript**, including demo/uikit views. Use `vue-tsc --noEmit` for SFC-aware typechecking; keep Encore's `enableTypeScriptLoader()` for transpilation only.
7. Reuse from Lamiral: **Tier A frontend infrastructure plus the auth backend and the mail backend only**. Do not port report/invoice/pricing/Dropbox layers. Authentication uses a `localStorage` access token plus an httpOnly refresh cookie (Lamiral-style); this XSS exposure is an accepted trade-off. Mail uses the `MailRouter`/`MailRun*` lifecycle with Mailpit in dev.
8. PrimeUI PRO Charts are **out of scope** (Community does not include them). The two core `Chart` usages in Sakai demo views must be replaced with direct Chart.js usage or removed, because core `Chart` is deprecated in v5 (removal planned for v6).

### License inventory (from PrimeUI Store, confirmed 2026-11-24)

- Tier: **Community**
- License type (as displayed): **dev**
- Seats: **4**
- Created: **2026-11-14**
- Valid until: **2027-11-14** (12-month term; renew before this date to avoid a license notice)
- Key: available in the store but intentionally not captured here — supply via `PRIMEUI_LICENSE_KEY` / `.env.local`

The displayed type `dev` is treated as a store label, not a usage restriction: the official Community License explicitly permits commercial projects and unlimited applications/deployments. Confirm with PrimeTek if that label carries additional constraints.

## Alternatives considered

### Target PrimeVue 4.5.x first, then v5

- **Pros:** stays on MIT-licensed versions; splits the largest breaking change from v5 deprecation work.
- **Cons:** duplicate migration effort; we already hold a Community license, and v5 has no breaking public API changes from v4.
- **Not chosen**, but remains the fallback if the Community license lapses or its eligibility no longer applies (MIT versions remain MIT forever).

### Upgrade the current Encore/Sakai fork in place

- **Pros:** smaller diff, lower short-term regression risk.
- **Cons:** does not deliver current upstream Sakai UI; still requires the full PrimeVue 3→5 migration.
- **Not chosen** — the user selected Strategy A.

### Migrate the bundler to Vite to match upstream Sakai

- **Pros:** zero friction with upstream `vite.config.mjs`.
- **Cons:** reverses ADR 0001; Symfony Reprise requires PHP 8.4/Symfony 8; large scope increase.
- **Rejected.**

### Use PrimeUI PRO Charts

- **Pros:** supported charting component in v5+.
- **Cons:** requires a separate/commercial license; only two demo views use charts.
- **Rejected** for now; direct Chart.js is sufficient.

## Rationale

- The Community license covers every core component needed by the starter, so there is no functional reason to remain on v4.
- v5 is the current release and has no breaking public API changes from v4, so the real work is the single v3→v5 migration (renames, removals, theming, Tailwind).
- Tailwind 4 via PostCSS preserves upstream Sakai utility classes while keeping the Encore decision intact.
- A pinned upstream reference keeps future Sakai merges tractable despite upstream being JS/Vite.
- Convert everything to TS now to avoid a long-lived mixed JS/TS frontend; `vue-tsc` makes the gate real.
- Lamiral's fetch wrapper, services, Pinia stores, auth backend, and mail backend are useful; its report/invoice/pricing/Dropbox layers are application-specific and would turn the starter into a product.

## Consequences

- Every v3-era PrimeVue component usage must be renamed or rewritten; removed APIs cannot be carried forward.
- Vue moves from 3.4 to 3.5 because PrimeVue 5 requires it; Pinia is pinned to a release compatible with that Vue range.
- Theming moves to `@primeuix/themes`; PrimeFlex classes are removed in favour of Tailwind utilities; PrimeVue/Tailwind cascade layers must be ordered per official guidance.
- The license key becomes a required environment value in every environment (including CI/prod builds or runtime). A missing/invalid key may display a license notice.
- Community license eligibility and renewal (12 months) must be tracked.
- All `.vue` SFCs and `.js` modules are rewritten; `vue-tsc`, TS-aware ESLint, and build/lint/typecheck become required verification gates.
- Auth adds entities, migrations, Lexik JWT/refresh tokens, CSRF cookie, and a documented `localStorage` risk acceptance.
- Mail adds `MailRun`/mail entities, MailRouter configuration, and dev-only Mailpit integration.
- Future Sakai upgrades require re-applying the local TS/Encore/Tailwind patch set; document it.

## Revisit

Re-evaluate when:

- The Community license expires or eligibility changes (fall back to MIT PrimeVue 4, or buy Commercial).
- PRO components (Scheduler, Charts, Text Editor, Task Board, Diagram) become a product requirement.
- Symfony 8 / PHP 8.4 makes Vite/Reprise viable again (supersedes ADR 0001).
- The `localStorage` token risk is no longer acceptable (move to memory-only + refresh-cookie bootstrap).

## Sources

- <https://primevue.org/migration/v5/> — no breaking public API changes from v4; removals and deprecations (including `Chart` → PRO Charts)
- <https://primeui.dev/licenses/community> — Community includes full core libraries; exclusions; seats, term, offline verification
- <https://primeui.dev/pricing> — Community vs Commercial feature matrix and PRO component list
- <https://primeuipro.dev/> — PRO component families
- <https://primevue.org/configuration/#license> and <https://primevue.org/llms/pages/configuration.md> — `license` config option
- <https://registry.npmjs.org/@primevue/core/5.0.5> — package peer dependency on Vue `^3.5.0`
- <https://tailwindcss.com/docs/installation/using-postcss> — Tailwind 4 PostCSS installation
- <https://symfony.com/doc/current/frontend/encore/postcss.html> — Encore `enablePostCssLoader()`
- <https://primevue.org/tailwind/> — `tailwindcss-primeui`, CSS layers, dark-mode alignment
- <https://github.com/primefaces/sakai-vue> — upstream Sakai 5.0.0
