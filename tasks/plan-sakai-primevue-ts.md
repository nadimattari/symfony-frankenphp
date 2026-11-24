# Implementation Plan: PrimeVue 5 / Sakai 5 / TypeScript / Tailwind / Lamiral reuse

## Overview

Upgrade this Encore-based Symfony/Vue starter to a current, TypeScript-first frontend:

- PrimeVue **3.51.0 → 5.0.5** with the held **PrimeUI Community License**
- Upstream **Sakai Vue 5.0.0** (`ec6b6ef...`) ported onto Webpack Encore
- All JS modules and all `.vue` SFCs converted to **TypeScript**, checked with `vue-tsc`
- **Tailwind CSS 4** via PostCSS in Encore (replacing PrimeFlex and the legacy SCSS utility layer)
- Reuse of Lamiral's frontend infrastructure (Tier A) plus its **auth** and **mail** backends only

Lamiral is a completed project and is treated as **read-only reference only**. No changes will be made there.

Detailed analysis: `docs/analysis-lamiral-sakai-upgrade.md`.
Decision record: `docs/adr/0002-primevue-version-and-sakai-strategy.md`.

This plan is additive. Existing `tasks/plan.md` and `tasks/todo.md` document the completed FrankenPHP/Symfony migration and are intentionally left untouched. Work happens on branch `feature/primevue-sakai-ts`.

## Architecture decisions (accepted)

- Keep Webpack Encore (ADR 0001); do not adopt Vite.
- Target PrimeVue **5.0.5** directly; v5 has no breaking public component API changes from v4. MIT 4.5.x is the fallback if the Community license lapses.
- License key comes from `PRIMEUI_LICENSE_KEY` (gitignored `.env.local`), exposed to the frontend through Twig and passed to `app.use(PrimeVue, { license })`. Never commit the key.
- Tailwind 4 via `.enablePostCssLoader()` + `@tailwindcss/postcss` + `tailwindcss-primeui`; remove PrimeFlex. Use `@primeuix/themes` for PrimeVue theming.
- Port upstream Sakai 5.0.0 as a pinned reference; local TS/Encore/Tailwind adaptations are the fork.
- All new/port files are TS; convert every remaining SFC to `<script setup lang="ts">`. `vue-tsc --noEmit` is a required gate.
- Reuse Lamiral Tier A + auth + mail. Exclude report builders, invoices, pricing, and Dropbox layers.
- Auth uses `localStorage` access token + httpOnly refresh cookie + CSRF cookie (accepted XSS trade-off).
- Core `Chart` is deprecated in v5 and PRO Charts are excluded by Community; replace the two chart demo usages with direct Chart.js or remove them.

## Task list

Tasks are tracked in `tasks/todo-sakai-primevue-ts.md`.

### Phase 0 — Baseline and reference

- Task 1: Verify baseline commands and upstream reference commit
- Task 2: Add `PRIMEUI_LICENSE_KEY` env plumbing (no key in git)

**Checkpoint: Baseline** — build/typecheck/test pass before UI changes; license env resolves in every environment.

### Phase 1 — Tooling foundation

- Task 3: Update frontend dependencies to PrimeVue 5 + Tailwind 4 + TS tooling
- Task 4: Configure `vue-tsc`, TS-aware ESLint, and SFC shims
- Task 5: Enable Encore PostCSS + Tailwind 4 + `tailwindcss-primeui`
- Task 6: Wire the PrimeUI license key and PrimeVue 5 theme config
- Task 7: Document verification commands and add lint script

**Checkpoint: Tooling** — build, typecheck, lint, and a no-license-notice browser smoke pass.

### Phase 2 — Port Sakai 5 onto Encore (TypeScript)

- Task 8: Port app entry and router to TypeScript
- Task 9: Port layout composable and theme configuration
- Task 10: Port primary layout shell components
- Task 11: Port menu/sidebar/footer components
- Task 12: Port dashboard, landing, and auth views
- Task 13: Port/convert all uikit and utilities demo SFCs
- Task 14: Verify Tailwind layer order and dark-mode alignment

**Checkpoint: Sakai port** — all routes render; no `.js` modules or plain `<script>` SFCs remain.

### Phase 3 — PrimeVue 3→5 migration cleanup

- Task 15: Migrate removed form and overlay components
- Task 16: Migrate removed table, panel, and list components
- Task 17: Migrate removed/deprecated menu, media, and misc components
- Task 18: Replace deprecated core `Chart` with direct Chart.js
- Task 19: Remove v3 theme/PrimeFlex imports and CSS classes

**Checkpoint: PrimeVue 5** — removed APIs and v3 theme imports are gone; visual smoke of every route.

### Phase 4 — Lamiral Tier A reuse

- Task 20: Add typed fetch wrapper, CSRF handling, and API types
- Task 21: Replace mock services with typed API services
- Task 22: Add Pinia stores (app + auth shell)
- Task 23: Port generic formatting utilities with configurable locale/timezone

**Checkpoint: Reuse** — one real API-backed route works; no Lamiral-domain endpoints copied verbatim.

### Phase 5 — Auth backend

- Task 24: Write an ADR for auth architecture (JWT, refresh tokens, entities, CSRF, token storage)
- Task 25: Add User/RefreshToken entities, migration, and repository
- Task 26: Add Lexik JWT config, login/refresh/logout endpoints, and security rules
- Task 27: Wire typed auth service/store to localStorage + refresh cookie
- Task 28: Verify auth flow (login, refresh, logout, 401 retry) and document XSS risk

**Checkpoint: Auth** — login/refresh/logout work end-to-end; no secrets committed.

### Phase 6 — Mail backend

- Task 29: Write an ADR for mail architecture (MailRun lifecycle, test redirect, Mailpit)
- Task 30: Add mail entities/configuration (from, reply-to, test redirect, throttle)
- Task 31: Port `MailRouter` and minimal `MailRunManager`/dispatcher with dev-only worker behaviour
- Task 32: Add typed mail settings/service and UI for test-mode banner
- Task 33: Verify mail send/test redirect and dev Mailpit delivery

**Checkpoint: Mail** — test email delivered to Mailpit in dev; production redirection config documented.

### Phase 7 — Final verification

- Task 34: Final build/typecheck/lint/PHPUnit/prod-image verification and docs update

**Checkpoint: Complete** — all success criteria below are met.

## Success criteria

- `npm ci && npm run build` succeeds in `sf-node`.
- `npm run typecheck` uses `vue-tsc --noEmit` and passes with `strict: true`.
- `npm run lint` passes with TS/Vue rules.
- `PRIMEUI_LICENSE_KEY` is required/documented and never committed; no license notice appears in the browser.
- No `.js` frontend modules remain; every SFC uses `lang="ts"`.
- No removed v3 PrimeVue imports/components, v3 theme imports, or PrimeFlex imports remain.
- Tailwind 4 + `tailwindcss-primeui` generate the Sakai utility classes; dark mode and cascade layers match the official guide.
- All Sakai routes render in dev; prod Encore build succeeds.
- Auth login/refresh/logout and mail test send work (or are explicitly marked as follow-ups in the ADRs).
- PHPUnit smoke test still passes; prod image still builds.
- Docs and ADRs reflect the implemented choices.

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Community license expires / eligibility changes | Medium | Track renewal; MIT PrimeVue 4.5.x fallback. |
| License key accidentally committed | Medium | Env-only injection, `.env.local` gitignored, secret scan before commits. |
| Tailwind/PrimeVue layer or dark-mode mismatch | Medium | Follow official PrimeVue Tailwind guide; visual smoke per checkpoint. |
| Upstream Sakai drift | Medium | Pin tag/commit, pristine reference, document local patches. |
| Large all-SFC TS conversion | Medium | Port one slice at a time; `vue-tsc` gates each checkpoint. |
| Auth `localStorage` XSS exposure | Medium | Accepted by decision; short TTL, httpOnly refresh cookie, CSRF cookie, XSS hygiene. |
| Lamiral auth/mail scope creep into reports/invoices | Medium | Explicit exclusions in ADRs and task acceptance criteria. |
| No frontend test suite | Medium | Build + typecheck + lint + browser smoke at each checkpoint. |

## Sources

See `docs/analysis-lamiral-sakai-upgrade.md` §10 and `docs/adr/0002-primevue-version-and-sakai-strategy.md`.
