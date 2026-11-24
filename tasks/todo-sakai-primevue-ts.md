# Task List: PrimeVue 5 / Sakai 5 / TypeScript / Tailwind / Lamiral reuse

Companion to `tasks/plan-sakai-primevue-ts.md`. Analysis: `docs/analysis-lamiral-sakai-upgrade.md`. ADR: `docs/adr/0002-primevue-version-and-sakai-strategy.md`.

Branch: `feature/primevue-sakai-ts`. Lamiral is read-only reference.

## Phase 0 — Baseline and reference

- [x] Task 1: Verify baseline commands and upstream reference
  - Acceptance: current `npm run build`, `npm run typecheck`, PHPUnit pass; upstream tag `5.0.0` / commit `ec6b6ef...` is recorded and a pristine reference exists or a reproducible fetch command is documented.
  - Verified 2026-11-24: build, typecheck and PHPUnit (1 test) pass; `git ls-remote` resolves `5.0.0` to `ec6b6ef53ad4fa7f2861277f50f2d9e236c40372`; reproducible tarball fetch command recorded in the analysis doc.
  - Files: `docs/analysis-lamiral-sakai-upgrade.md` (reference section)
  - Dependencies: None
  - Scope: XS

- [x] Task 2: Add `PRIMEUI_LICENSE_KEY` env plumbing
  - Acceptance: key read from env (gitignored `.env.local` for local dev), exposed to Twig and ready for frontend injection; no key value in tracked files; docs explain annual renewal, record the 2027-11-14 expiry (4 seats), and note that the key may appear in the bundle.
  - Verified 2026-11-24: Twig lint passes in dev/test; `app.primeui_license_key` resolves from env; `GET /` returns HTTP 200 with `window.PRIMEUI_LICENSE_KEY = ''`; `git grep` shows variable names/docs only, no key value.
  - Files: `.env`, `config/services.yaml`, `config/packages/twig.yaml`, `templates/base.html.twig`, `README.md`
  - Dependencies: Task 1
  - Scope: S

**Checkpoint: Baseline**
- [x] Build, typecheck, and PHPUnit pass before UI changes
- [x] License env resolves in dev/test/prod without committing the key

## Phase 1 — Tooling foundation

- [x] Task 3: Update frontend dependencies
  - Acceptance: `primevue@5.0.5`, `@primeuix/themes`, `tailwindcss@^4`, `@tailwindcss/postcss`, `tailwindcss-primeui`, `postcss`, `pinia`, `vue-tsc`, TS ESLint packages installed; `primeflex` and `@babel/*` legacy packages removed if unused; lockfile updated.
  - Verified 2026-11-24: `npm ci`, `npm run build` and `npm run typecheck` pass. Keeping Encore 4 requires ESLint 8.57.1 (Encore peer is `^8`) and postcss-loader 8 with Encore bumped to 4.7.0. PrimeVue 5 removed several v3 modules, so the current entry now maps removed components/directives to v5 equivalents and `primevue/api` imports moved to `@primevue/core/api`; these aliases are temporary until the Sakai port replaces them. `primeflex` and `@babel/*` remain because PrimeFlex classes are still used by current templates and Encore 4 declares Babel as a peer.
  - Files: `package.json`, `package-lock.json`, `assets/app.js`, `assets/views/pages/Crud.vue`, `assets/views/uikit/Table.vue`
  - Dependencies: Task 2
  - Scope: M

- [x] Task 4: Configure `vue-tsc` and TS-aware ESLint
  - Acceptance: `typecheck` script is `vue-tsc --noEmit`; ESLint parses `.ts` and `<script setup lang="ts">`; `eslint-plugin-prettier` is a direct devDependency; `assets/shims-vue.d.ts` added only if required.
  - Verified 2026-11-24: `npm run typecheck`, `npm run lint` and `npm run build` pass. TypeScript was raised to 5.9.3 because vue-tsc 3 needs the newer compiler API. No `.vue` shim is needed. ESLint 8.57.1 is retained for the Encore 4 peer range. Two Vue naming rules were disabled for upstream Sakai's single-word view/component names. Prettier reports pre-existing formatting warnings only (0 errors).
  - Files: `package.json`, `package-lock.json`, `tsconfig.json`, `eslint.config.mjs`
  - Dependencies: Task 3
  - Scope: S

- [x] Task 5: Enable Encore PostCSS + Tailwind 4
  - Acceptance: `webpack.config.js` calls `.enablePostCssLoader()`; `postcss.config.cjs` (CommonJS, matching current config style) registers `@tailwindcss/postcss`; CSS entry has `@import "tailwindcss";` and `@import "tailwindcss-primeui";`; PrimeVue/Tailwind layer order and dark variant follow official guidance.
  - Verified 2026-11-24: production build emits a ~381 KiB CSS bundle containing Tailwind 4 utilities (`.bg-primary`, custom 576px breakpoint) and the declared `theme, base, primevue, components, utilities` layer order. The CSS entry is `assets/assets/tailwind.css`, imported before the existing Sakai SCSS. PrimeFlex remains until the Sakai port replaces its classes.
  - Files: `webpack.config.js`, `postcss.config.cjs`, `assets/assets/tailwind.css`, `assets/app.js`
  - Dependencies: Task 3
  - Scope: M

- [ ] Task 6: Wire license key and PrimeVue 5 configuration
  - Acceptance: `assets/app.ts` passes `license` from exposed global into `PrimeVue` plugin; `@primeuix/themes` preset configured; no license notice in browser; key absent from source.
  - Verify: browser console/DOM check for license notice; `git grep` for key value.
  - Files: `assets/app.ts`, `templates/base.html.twig`, `config/services.yaml`
  - Dependencies: Tasks 2, 5
  - Scope: S

- [ ] Task 7: Document verification commands and lint script
  - Acceptance: README lists build/typecheck/lint/test commands and license env setup; `npm run lint` works from Node container; Makefile targets added if needed.
  - Verify: run commands from README.
  - Files: `README.md`, `Makefile`, `package.json`
  - Dependencies: Task 4
  - Scope: S

**Checkpoint: Tooling**
- [ ] Build, typecheck, lint pass on the unchanged UI
- [ ] No license notice; Encore still 4.x; Vite not introduced

## Phase 2 — Port Sakai 5 onto Encore (TypeScript)

- [ ] Task 8: Port app entry and router to TypeScript
  - Acceptance: `assets/app.ts` registers PrimeVue 5, Pinia, services, directives; router typed with `RouteRecordRaw`; Encore entry updated from `app.js` to `app.ts`.
  - Verify: build, typecheck.
  - Files: `assets/app.ts`, `assets/router/index.ts`, `webpack.config.js`
  - Dependencies: Task 7
  - Scope: M

- [ ] Task 9: Port layout composable and theme configuration
  - Acceptance: `layout.ts` types layout config/state; `AppConfig.vue` uses PrimeVue 5 theme APIs (`inputVariant`, no `inputStyle`); dark mode/ripple/scale controls work.
  - Verify: typecheck; browser smoke of configurator.
  - Files: `assets/layout/composables/layout.ts`, `assets/layout/AppConfig.vue`
  - Dependencies: Task 8
  - Scope: M

- [ ] Task 10: Port primary layout shell components
  - Acceptance: AppLayout/AppSidebar/AppTopbar use `<script setup lang="ts">`; slots/menu toggles typed.
  - Verify: typecheck; browser smoke of `/` and `/dashboard`.
  - Files: `assets/layout/AppLayout.vue`, `assets/layout/AppSidebar.vue`, `assets/layout/AppTopbar.vue`
  - Dependencies: Task 9
  - Scope: M

- [ ] Task 11: Port menu/sidebar/footer components
  - Acceptance: AppMenu/AppMenuItem/AppFooter converted; menu model typed as `MenuItem[]`.
  - Verify: typecheck; navigation smoke.
  - Files: `assets/layout/AppMenu.vue`, `assets/layout/AppMenuItem.vue`, `assets/layout/AppFooter.vue`
  - Dependencies: Task 10
  - Scope: S

- [ ] Task 12: Port dashboard, landing, and auth views
  - Acceptance: routes render; widget data refs typed; PrimeVue 5 component names used; all script blocks are TS.
  - Verify: build, typecheck; browser smoke per route.
  - Files: `assets/views/Dashboard.vue`, `assets/views/pages/Landing.vue`, `assets/views/pages/auth/Login.vue`, `Access.vue`, `Error.vue`
  - Dependencies: Task 11
  - Scope: M

- [ ] Task 13: Port/convert all uikit and utilities demo SFCs
  - Acceptance: every remaining `.vue` uses `<script setup lang="ts">`; no `.js` frontend modules remain; router entries valid.
  - Verify: `find assets -name '*.js'` returns no source modules; build, typecheck.
  - Files: `assets/views/uikit/**`, `assets/views/utilities/**`, `assets/router/index.ts`
  - Dependencies: Task 12
  - Scope: L

- [ ] Task 14: Verify Tailwind layer order and dark-mode alignment
  - Acceptance: PrimeVue utilities override component styles without `!`; dark mode selector matches between PrimeVue and Tailwind; screenshots approximate pinned Sakai reference.
  - Verify: visual smoke of dashboard, form, overlay, dark mode.
  - Files: `assets/styles/**`, `assets/app.ts`, `assets/layout/AppConfig.vue`
  - Dependencies: Tasks 5, 13
  - Scope: S

**Checkpoint: Sakai port**
- [ ] All routes render; upstream UI structure present
- [ ] No `.js` frontend modules; every SFC uses `lang="ts"`

## Phase 3 — PrimeVue 3→5 migration cleanup

- [ ] Task 15: Migrate removed form and overlay components
  - Acceptance: Calendar→DatePicker, Dropdown→Select, InputSwitch→ToggleSwitch, Chips→InputTags, InputMask→Mask directive, OverlayPanel→Popover, InlineMessage→Message.
  - Verify: build, typecheck; form/overlay smoke.
  - Files: `assets/views/uikit/*`, `assets/layout/*`
  - Dependencies: Task 14
  - Scope: M

- [ ] Task 16: Migrate removed table, panel, and list components
  - Acceptance: TriStateCheckbox→Checkbox indeterminate, DataViewLayoutOptions→SelectButton, AccordionTab→AccordionPanel structure, TabView/TabPanel→Tabs structure.
  - Verify: typecheck; table/panel smoke.
  - Files: `assets/views/uikit/Table.vue`, `Panels.vue`, `List.vue`
  - Dependencies: Task 15
  - Scope: M

- [ ] Task 17: Migrate removed/deprecated menu, media, and misc components
  - Acceptance: Steps→Stepper, TabMenu→Tabs, `v-badge`→OverlayBadge; MultiSelect→Select multiple; Galleria→Gallery; Image→Gallery; ColorPicker→InputColor; ScrollPanel→ScrollArea; Password→InputPassword; ImageCompare→Compare.
  - Verify: build, typecheck; route smoke.
  - Files: `assets/views/uikit/Menu.vue`, `Media.vue`, `Misc.vue`
  - Dependencies: Task 16
  - Scope: M

- [ ] Task 18: Replace deprecated core `Chart`
  - Acceptance: `assets/views/uikit/Chart.vue` and dashboard chart usage no longer import `primevue/chart`; direct Chart.js (typed) or removal is implemented; decision documented.
  - Verify: build, typecheck; chart route renders with no deprecation warnings.
  - Files: `assets/views/uikit/Chart.vue`, `assets/views/Dashboard.vue`, `assets/app.ts`
  - Dependencies: Task 17
  - Scope: S

- [ ] Task 19: Remove v3 theme/PrimeFlex imports and CSS classes
  - Acceptance: no `primevue/resources`, `theme.css`, `primeflex`, `.p-link`, `.p-highlight`, `.p-fluid`, or `inputStyle` remain; 16px root font size or compat preset chosen.
  - Verify: `git grep -E 'primeflex|theme\.css|p-fluid|inputStyle'` clean; visual smoke.
  - Files: `assets/styles/**`, all SFCs
  - Dependencies: Task 18
  - Scope: S

**Checkpoint: PrimeVue 5**
- [ ] No removed v3 APIs or v3 theme imports remain
- [ ] No PRO component required; no license notice

## Phase 4 — Lamiral Tier A reuse

- [ ] Task 20: Add typed fetch wrapper and API types
  - Acceptance: `fetch.ts` supports single-flight refresh, CSRF cookie header, merge-patch, typed errors, blob downloads, configurable endpoints; DTOs in `assets/types/api.ts`.
  - Verify: typecheck; manual request smoke.
  - Files: `assets/utils/fetch.ts`, `assets/types/api.ts`
  - Dependencies: Task 7
  - Scope: M

- [ ] Task 21: Replace mock services with typed API services
  - Acceptance: service pattern ported without Lamiral-domain URLs; static demo services removed or clearly marked demo-only.
  - Verify: build; one API-backed route returns data.
  - Files: `assets/service/*.ts`
  - Dependencies: Task 20
  - Scope: M

- [ ] Task 22: Add Pinia stores
  - Acceptance: `pinia.ts` installed in `app.ts`; `auth.ts` typed with roles and `localStorage` token; token storage decision documented.
  - Verify: typecheck; login/logout shell smoke.
  - Files: `assets/store/pinia.ts`, `assets/store/auth.ts`, `assets/app.ts`
  - Dependencies: Task 21
  - Scope: M

- [ ] Task 23: Port generic formatting utilities
  - Acceptance: `common.ts` uses configurable locale/timezone; domain-only portal helpers excluded unless needed.
  - Verify: typecheck; simple runtime checks.
  - Files: `assets/utils/common.ts`
  - Dependencies: Task 20
  - Scope: S

**Checkpoint: Reuse**
- [ ] One real API-backed route works end-to-end
- [ ] No Lamiral-domain endpoints copied verbatim

## Phase 5 — Auth backend

- [ ] Task 24: Auth architecture ADR
  - Acceptance: new ADR covers JWT, refresh-token rotation, entities, migrations, CSRF, security config, `localStorage`+refresh-cookie decision, and XSS risk acceptance.
  - Verify: ADR committed and referenced from this task list.
  - Files: `docs/adr/0003-auth-architecture.md`
  - Dependencies: Task 1 decision (auth in scope)
  - Scope: S

- [ ] Task 25: User/RefreshToken entities and migration
  - Acceptance: Doctrine entities, repository, unique indexes, migration generated; raw tokens hashed (SHA-256) as in Lamiral.
  - Verify: `doctrine:migrations:migrate` on test DB; schema validate.
  - Files: `src/Entity/`, `src/Repository/`, `migrations/`
  - Dependencies: Task 24
  - Scope: L

- [ ] Task 26: JWT config and API endpoints
  - Acceptance: Lexik JWT installed/configured; `/api/login`, `/api/token/refresh`, `/api/logout`; CSRF cookie; API Platform docs disabled outside dev; keys generated per environment and gitignored.
  - Verify: curl flows; no keys in git.
  - Files: `composer.json`, `config/packages/*`, `config/jwt/` (ignored), `src/Controller/AuthController.php`, `src/Controller/TokenController.php`
  - Dependencies: Task 25
  - Scope: L

- [ ] Task 27: Wire typed frontend auth flow
  - Acceptance: `assets/service/auth.ts` and `store/auth.ts` use the fetch wrapper; 401 triggers refresh retry; roles/guards typed.
  - Verify: browser smoke of login/refresh/logout and protected route.
  - Files: `assets/service/auth.ts`, `assets/store/auth.ts`, `assets/router/index.ts`
  - Dependencies: Tasks 20–22, 26
  - Scope: M

- [ ] Task 28: Verify auth and document risk
  - Acceptance: login, refresh rotation/revocation, logout, CSRF, and XSS risk documented; no secrets in repo.
  - Verify: end-to-end smoke; secret scan.
  - Files: `README.md`, ADR 0003
  - Dependencies: Task 27
  - Scope: S

**Checkpoint: Auth**
- [ ] Full auth flow works; token storage decision implemented and documented

## Phase 6 — Mail backend

- [ ] Task 29: Mail architecture ADR
  - Acceptance: ADR covers MailRun lifecycle, test redirect, throttle, dev Mailpit, and explicit exclusion of report/invoice layers.
  - Verify: ADR committed.
  - Files: `docs/adr/0004-mail-architecture.md`
  - Dependencies: Task 1 decision (mail in scope)
  - Scope: S

- [ ] Task 30: Mail configuration and entities
  - Acceptance: `MAIL_FROM`, `MAIL_REPLY_TO`, `MAIL_REDIRECT_TO`, `MAIL_THROTTLE_SECONDS` wired; minimal MailRun/EmailLog entities and migration; dev Mailpit config retained.
  - Verify: config dump; schema validate.
  - Files: `.env`, `config/services.yaml`, `src/Entity/`, `migrations/`
  - Dependencies: Task 29
  - Scope: M

- [ ] Task 31: Port MailRouter and minimal MailRun lifecycle
  - Acceptance: test redirect, `[TEST]` subject rewrite, original-recipient header, run creation/claim/fail, dev-safe dispatch; no detached production worker without explicit config.
  - Verify: PHPUnit or manual service calls; config smoke.
  - Files: `src/Service/Mail/MailRouter.php`, `MailRunManager.php`, `MailRunDispatcher.php`, `ActiveMailRunException.php`
  - Dependencies: Task 30
  - Scope: L

- [ ] Task 32: Typed frontend mail settings and test banner
  - Acceptance: `assets/service/mail.ts` loads settings; test-mode banner typed; no domain-specific report endpoints.
  - Verify: typecheck; browser smoke.
  - Files: `assets/service/mail.ts`, `assets/components/MailTestModeBanner.vue`
  - Dependencies: Tasks 20, 31
  - Scope: S

- [ ] Task 33: Verify mail delivery
  - Acceptance: test email appears in Mailpit in dev; redirect config documented for non-dev.
  - Verify: Mailpit UI; curl API trigger.
  - Files: `README.md`, ADR 0004
  - Dependencies: Task 32
  - Scope: S

**Checkpoint: Mail**
- [ ] Mail test send works in dev; no report/invoice code ported

## Phase 7 — Final verification

- [ ] Task 34: Final verification and docs update
  - Acceptance: all success criteria in the plan are checked; README/SPEC/ADR/task docs reflect final versions, commands, and license setup.
  - Verify: `npm run build`, `npm run typecheck`, `npm run lint`, PHPUnit, prod image build, browser smoke of all routes.
  - Files: `README.md`, `SPEC.md`, `docs/**`, `tasks/**`
  - Dependencies: Tasks 14, 19, 23, 28, 33
  - Scope: S

**Checkpoint: Complete**
- [ ] Build, typecheck, lint, PHPUnit, and prod build pass
- [ ] ADR status and docs match implemented choices
- [ ] Ready for review

## Phase 8 — Future (not in scope)

- [ ] Task 35: Re-evaluate Symfony Reprise as an Encore replacement
  - Acceptance: a new ADR superseding ADR 0001 records the platform decision (PHP 8.4+, Node 22.18+/24.11+, Symfony LTS 7.4 vs 8, Vite 7/8 or Rsbuild) and a separate migration plan exists before any code changes.
  - Verify: ADR status links ADR 0001 as superseded; Encore removal is a dedicated commit/PR, not part of the Sakai/PrimeVue work.
  - Files: `docs/adr/0001-bundler.md`, new ADR, new task plan
  - Dependencies: platform-upgrade decision; not part of this branch's success criteria
  - Scope: S (decision only; migration scope to be estimated separately)
