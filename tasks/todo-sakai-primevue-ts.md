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
  - Verified 2026-11-24: Twig lint passes in dev/test; `app.primeui_license_key` resolves from env; `GET /` returns HTTP 200 with `window.PRIMEUI_LICENSE_KEY = ''`; `git grep` shows variable names/docs only, no key value. Task 7 found and fixed a regression: Symfony treats the intentionally empty `.env` value as missing, so `config/services.yaml` now declares `env(PRIMEUI_LICENSE_KEY): ''`; PHPUnit passes again.
  - Files: `.env`, `config/services.yaml`, `config/packages/twig.yaml`, `templates/base.html.twig`, `README.md`
  - Dependencies: Task 1
  - Scope: S

**Checkpoint: Baseline**
- [x] Build, typecheck, and PHPUnit pass before UI changes
- [x] License env resolves in dev/test/prod without committing the key

## Phase 1 — Tooling foundation

- [x] Task 3: Update frontend dependencies
  - Acceptance: `primevue@5.0.5`, `@primeuix/themes`, `tailwindcss@^4`, `@tailwindcss/postcss`, `tailwindcss-primeui`, `postcss`, `pinia`, `vue-tsc`, TS ESLint packages installed; `primeflex` and `@babel/*` legacy packages removed if unused; lockfile updated.
  - Verified 2026-11-24: `npm ci`, `npm run build` and `npm run typecheck` pass. Keeping Encore 4 requires ESLint 8.57.1 (Encore peer is `^8`) and postcss-loader 8 with Encore bumped to 4.7.0. Vue is locked to 3.5.46 because PrimeVue 5 declares a `vue@^3.5.0` peer; the old 3.4 baseline is incompatible. PrimeVue 5 removed several v3 modules, so the current entry now maps removed components/directives to v5 equivalents and `primevue/api` imports moved to `@primevue/core/api`; these aliases are temporary until the Sakai port replaces them. `primeflex` and `@babel/*` remain because PrimeFlex classes are still used by current templates and Encore 4 declares Babel as a peer.
  - Files: `package.json`, `package-lock.json`, `assets/app.js`, `assets/views/pages/Crud.vue`, `assets/views/uikit/Table.vue`
  - Dependencies: Task 2
  - Scope: M

- [x] Task 4: Configure `vue-tsc` and TS-aware ESLint
  - Acceptance: `typecheck` script is `vue-tsc --noEmit`; ESLint parses `.ts` and `<script setup lang="ts">`; `eslint-plugin-prettier` is a direct devDependency; `assets/shims-vue.d.ts` added only if required.
  - Verified 2026-11-24: `npm run typecheck`, `npm run lint` and `npm run build` pass. TypeScript was raised to 5.9.3 because vue-tsc 3 needs the newer compiler API. ESLint 8.57.1 is retained for the Encore 4 peer range. Two Vue naming rules were disabled for upstream Sakai's single-word view/component names. Prettier reports formatting warnings only (0 errors). The `.vue` module shim was added in Task 6 when the entry became TypeScript.
  - Files: `package.json`, `package-lock.json`, `tsconfig.json`, `eslint.config.mjs`
  - Dependencies: Task 3
  - Scope: S

- [x] Task 5: Enable Encore PostCSS + Tailwind 4
  - Acceptance: `webpack.config.js` calls `.enablePostCssLoader()`; `postcss.config.cjs` (CommonJS, matching current config style) registers `@tailwindcss/postcss`; CSS entry has `@import "tailwindcss";` and `@import "tailwindcss-primeui";`; PrimeVue/Tailwind layer order and dark variant follow official guidance.
  - Verified 2026-11-24: production build emits a ~381 KiB CSS bundle containing Tailwind 4 utilities (`.bg-primary`, custom 576px breakpoint). The CSS entry is `assets/assets/tailwind.css`, imported before the existing Sakai SCSS. Task 6 corrected the declared order to `theme, base, primeui, components, utilities` to match the layer PrimeVue 5 actually emits. PrimeFlex remains until the Sakai port replaces its classes.
  - Files: `webpack.config.js`, `postcss.config.cjs`, `assets/assets/tailwind.css`, `assets/app.js`
  - Dependencies: Task 3
  - Scope: M

- [x] Task 6: Wire license key and PrimeVue 5 configuration
  - Acceptance: `assets/app.ts` passes `license` from exposed global into `PrimeVue` plugin; `@primeuix/themes` preset configured; no license notice in browser; key absent from source.
  - Verified 2026-11-24: entry renamed to TypeScript, `AuraCompat` configured with `.app-dark` dark selector and `cssLayer: true`; `license` comes from `window.PRIMEUI_LICENSE_KEY`. Build/typecheck/lint pass. Browser shows the app mounted and 29 PrimeVue style tags in the `primeui` layer. With an empty key PrimeUI correctly warned "license is not configured" and showed its banner; after the real key was placed in gitignored `.env.local`, the page receives it (length 262 only, value never printed), no license notice is rendered, and no PrimeUI console warning appears. `.env.local` is confirmed ignored by git, and `git grep`/`git log -S` show only variable names/docs, never a key value. Official PrimeUI guidance confirms the plugin `license` option is correct for PrimeVue 5.0.0+ and that no separate Pro key is required for this app: https://blog.primeui.dev/blog/setting-up-a-primeui-license-key/
  - Files: `assets/app.ts`, `assets/shims-vue.d.ts`, `assets/types/window.d.ts`, `assets/assets/tailwind.css`, `webpack.config.js`, `tsconfig.json`, `eslint.config.mjs`
  - Dependencies: Tasks 2, 5
  - Scope: S

- [x] Task 7: Document verification commands and lint script
  - Acceptance: README lists build/typecheck/lint/test commands and license env setup; `npm run lint` works from Node container; Makefile targets added if needed.
  - Verified 2026-11-24: README now lists exact `sf-node` commands for `npm ci`, build, typecheck, lint and PHPUnit, plus the empty-key banner behavior. All were run successfully. Lint exits 0 with Prettier warnings only. No Makefile target was needed. Verification exposed a missing-env regression in test; fixed via the explicit empty default in `config/services.yaml`.
  - Files: `README.md`, `config/services.yaml`, `tasks/todo-sakai-primevue-ts.md`
  - Dependencies: Task 4
  - Scope: S

**Checkpoint: Tooling**
- [x] Build, typecheck, lint pass on the unchanged UI (verified 2026-11-24)
- [x] No license notice; Encore still 4.x; Vite not introduced (valid key verified in gitignored `.env.local` on 2026-11-24)

## Phase 2 — Port Sakai 5 onto Encore (TypeScript)

- [x] Task 8: Port app entry and router to TypeScript
  - Acceptance: `assets/app.ts` registers PrimeVue 5, Pinia, services, directives; router typed with `RouteRecordRaw`; Encore entry updated from `app.js` to `app.ts`.
  - Verified 2026-11-24: entry and Encore update were completed in Task 6; Task 8 adds `createPinia()` and converts `assets/router/index.js` to `index.ts` with a `RouteRecordRaw[]` route table. `npm ci`, build, typecheck and lint pass; browser smoke shows the app mounted with 30 router links and no console errors. Vue is locked to 3.5.46 because PrimeVue 5 requires `vue@^3.5.0`.
  - Files: `assets/app.ts`, `assets/router/index.ts`, `package.json`, `package-lock.json`, `AGENTS.md`, `README.md`, `docs/adr/0002-primevue-version-and-sakai-strategy.md`
  - Dependencies: Task 7
  - Scope: M

- [x] Task 9: Port layout composable and theme configuration
  - Acceptance: `layout.ts` types layout config/state; `AppConfig.vue` uses PrimeVue 5 theme APIs (`inputVariant`, no `inputStyle`); dark mode/ripple/scale controls work.
  - Verified 2026-11-24: `layout.js` → typed `layout.ts` with `LayoutConfig`/`LayoutState` and upstream-style helpers (`toggleDarkMode`, `toggleConfigSidebar`, `toggleMenu`, `hideMobileMenu`, `changeMenuMode`, `isDesktop`, `hasOpenOverlay`). `toRefs` and legacy field names are kept as a bridge because 11 not-yet-ported shell/view components still read `.value`; Tasks 10–13 must remove that bridge and the legacy `theme` change signal. `AppConfig.vue` is now `<script setup lang="ts">`: `$primevue.changeTheme`/`inputStyle` and the v3 CSS theme swatches are gone, replaced by `$t`, `updatePreset`, `updateSurfacePalette`, `config.inputVariant`, `config.ripple` and `.app-dark`. Added `:root.app-dark { color-scheme: dark; }` to `styles.scss` because the legacy v3 `theme.css` still linked in Twig sets an unlayered `color-scheme: light` that outranks PrimeVue's layered dark rule; retire it with the SCSS port. Upstream's `startViewTransition` wrapper was omitted because its async callback can be skipped, leaving state and class out of sync.
  - Browser smoke: dark mode sets `.app-dark` and computed `color-scheme: dark`; scale 14px → 15px; ripple toggles `p-ripple-disabled`; primary token `--p-primary-500` changes `#10b981` → `#3b82f6`; Nora preset selectable; 0 console errors. Build, typecheck, lint (0 errors) and PHPUnit pass.
  - Files: `assets/layout/composables/layout.ts`, `assets/layout/AppConfig.vue`, `assets/assets/styles.scss`
  - Dependencies: Task 8
  - Scope: M

- [x] Task 10: Port primary layout shell components
  - Acceptance: AppLayout/AppSidebar/AppTopbar use `<script setup lang="ts">`; slots/menu toggles typed.
  - Note: the legacy v3 `theme.css` link and `color-scheme` override stay until the Sakai SCSS moves to v5 tokens (Tasks 14/18). `useLayout()` remains the `toRefs` bridge for the not-yet-ported menu/views; remove it after Tasks 11-13.
  - Verified 2026-11-24: added `useLayoutState()` as the raw reactive API matching upstream Sakai 5 (`https://github.com/primefaces/sakai-vue/tree/5.0.0/src/layout`); the shell uses it while `useLayout()` keeps the compatibility bridge. `AppLayout` drops the dead `layout-theme-light/dark` classes and delegates mask clicks to `hideMobileMenu`. `AppSidebar` now owns the `.layout-sidebar` root, watches `route.path` to set `activePath`/clear menu state, and binds its desktop outside-click listener only while the overlay is open. `AppTopbar` has typed handlers and null-safe outside-click detection. Adding upstream's `state.activeMenuItem` early broke the still-JS `AppMenuItem.vue` (`startsWith` called on a Ref), so that field is deferred to Task 11.
  - Browser smoke: `/` renders the dashboard; static menu toggles; overlay opens and closes on outside click; topbar menu toggles and closes on outside click; client-side navigation to `/uikit/formlayout` keeps the shell and active route; 0 console errors. `/dashboard` is not a route in this app (the dashboard is `/`, and Symfony has no SPA catch-all), so nested views were checked through client-side navigation instead.
  - Also disabled base `no-undef`/`no-unused-vars` for TS/Vue files in `eslint.config.mjs`; TypeScript and `@typescript-eslint` handle type identifiers correctly. Build, typecheck, lint (0 errors) and PHPUnit pass.
  - Files: `assets/layout/AppLayout.vue`, `assets/layout/AppSidebar.vue`, `assets/layout/AppTopbar.vue`, `assets/layout/composables/layout.ts`, `eslint.config.mjs`
  - Dependencies: Task 9
  - Scope: M

- [x] Task 11: Port menu/sidebar/footer components
  - Acceptance: AppMenu/AppMenuItem/AppFooter converted; menu model typed as `MenuItem[]`.
  - Verified 2026-11-24: added `assets/layout/types/menu.ts`, extending PrimeVue's official `MenuItem` with Sakai's `path` field (`https://github.com/primefaces/sakai-vue/blob/5.0.0/src/layout/AppMenu.vue`). Ported the menu to upstream's path-based activation: `fullPath`/`isActive`, hover expansion, and `activePath`-driven submenu visibility. Route paths were kept local (`/pages/timeline`, `/documentation`, etc.) rather than copied verbatim from upstream. `AppSidebar` now syncs `activePath` on desktop as well as mobile so the active branch tracks navigation. Removed the obsolete `activeMenuItem` config field, `setActiveMenuItem`, and `ActiveMenuItem` type; `useLayout()` is now only needed by AppConfig and the remaining views. `AppFooter` uses upstream's simplified markup.
  - Browser smoke: `/` highlights Dashboard; clicking Pages/Auth expands their submenus; client-side navigation to `/uikit/formlayout` highlights both the UI Components branch and the Form Layout leaf; `/auth/login` correctly renders outside AppLayout; 0 console errors. Build, typecheck, lint (0 errors) and PHPUnit pass.
  - Files: `assets/layout/AppMenu.vue`, `assets/layout/AppMenuItem.vue`, `assets/layout/AppFooter.vue`, `assets/layout/AppSidebar.vue`, `assets/layout/composables/layout.ts`, `assets/layout/types/menu.ts`
  - Dependencies: Task 10
  - Scope: S

- [x] Task 12: Port dashboard, landing, and auth views
  - Acceptance: routes render; widget data refs typed; PrimeVue 5 component names used; all script blocks are TS.
  - Verify: build, typecheck; browser smoke per route.
  - Files: `assets/views/Dashboard.vue`, `assets/views/pages/Landing.vue`, `assets/views/pages/auth/Login.vue`, `Access.vue`, `Error.vue`
  - Dependencies: Task 11
  - Scope: M
  - Notes: Dashboard split into typed widgets; landing ported to Sakai 5 widget composition; auth views converted to TS + Sakai 5 Tailwind/PrimeVue 5 markup. Replaced `rounded-full` with inline `border-radius: 50%` to avoid postcss-calc `infinity * 1px` warning. Verified with `npm run build`, `npm run typecheck`, browser smoke on `/`, `/landing`, `/auth/login`, `/auth/access`, `/auth/error` via client-side navigation (direct nested SPA URLs 404 through Symfony). PHPUnit `OK (1 test, 1 assertion)`.

- [x] Task 13: Port/convert all uikit and utilities demo SFCs
  - Acceptance: every remaining `.vue` uses `<script setup lang="ts">`; no `.js` frontend modules remain; router entries valid.
  - Verified 2026-11-24: all 52 SFCs (including `App.vue`) now use `lang="ts"`, and `find assets -name '*.js'` is empty. Converted the last uikit batches (Button, Chart, FormLayout, Input, List, Menu, Misc, Overlay, Panels, Table) and page demos (Crud, Empty, NotFound, Timeline). `vue-tsc --noEmit` is the authoritative type gate; `webpack.config.js` now enables `transpileOnly` for Encore's ts-loader because ts-loader reports implicit-`any` slot params that `vue-tsc` accepts. Slot props that PrimeVue 5 does not declare are narrowed with `(slotProps as any)` in templates, and v5 slot names are used (`#option` for PickList/OrderList). Demo data/image URLs still resolve relative to the SPA path (e.g. `/uikit/demo/data/*.json` 404 during nested-route smoke); this predates the TS port and is tracked for Task 21.
  - Verify: `find assets -name '*.js'` returns no source modules; build, typecheck; browser smoke per route.
  - Browser smoke: client-side navigation reached all converted routes with their expected markers visible (form layout, input, button, table, list, panel, overlay, menu, charts, misc, crud, timeline, notfound); no console errors beyond the pre-existing demo-data/image 404s noted above.
  - Files: `assets/views/uikit/**`, `assets/views/utilities/**`, `assets/views/pages/**`, `assets/App.vue`, `assets/router/index.ts`, `webpack.config.js`
  - Dependencies: Task 12
  - Scope: L

- [x] Task 14: Verify Tailwind layer order and dark-mode alignment
  - Acceptance: PrimeVue utilities override component styles without `!`; dark mode selector matches between PrimeVue and Tailwind; screenshots approximate pinned Sakai reference.
  - Verified 2026-11-24: official PrimeVue/Tailwind guide and pinned upstream Sakai 5 CSS both use the `[class*="app-dark"]` custom variant, matching our `darkModeSelector: '.app-dark'`. Browser inspection found two cascade defects in the Encore setup: the legacy v3 `theme.css` wrapped its component rules in an undeclared `primevue` layer (appended after `utilities`), and PrimeVue injects `@layer primeui;` as the first `<head>` child (putting component styles below Tailwind's `base` reset). Fixed by declaring the full order in `tailwind.css` and passing `cssLayer: { name: 'primeui', order: 'primevue, theme, base, primeui, components, utilities' }` in `app.ts`. Runtime check: PrimeVue button base `border-radius` 6px, adding Tailwind `.rounded` yields 3.5px with no `!important`; layer statements resolve to `primevue → theme → base → primeui → components → utilities`. Dark toggle adds `.app-dark`, flips `color-scheme` light→dark, and `dark:text-surface-0` resolves `rgb(15,23,42)`→`rgb(255,255,255)`. Visual smoke: dashboard, form layout and overlay render expected markers with non-collapsed layout. Screenshot capture was unavailable (desktop browser window not visible in this environment), so visual checks used computed styles and DOM geometry.
  - Verify: visual smoke of dashboard, form, overlay, dark mode.
  - Files: `assets/assets/tailwind.css`, `assets/app.ts`
  - Dependencies: Tasks 5, 13
  - Scope: S

**Checkpoint: Sakai port**
- [x] All routes render; upstream UI structure present (verified 2026-11-24 via client-side navigation; demo data/image 404s remain for Task 21)
- [x] No `.js` frontend modules; every SFC uses `lang="ts"` (verified 2026-11-24)

## Phase 3 — PrimeVue 3→5 migration cleanup

- [x] Task 15: Migrate removed form and overlay components
  - Acceptance: Calendar→DatePicker, Dropdown→Select, InputSwitch→ToggleSwitch, Chips→InputTags, InputMask→Mask directive, OverlayPanel→Popover, InlineMessage→Message.
  - Verified 2026-11-24 in four slices (commits `6a57ab3`, `024500a`, `ddd60c3`, `503a165`): all seven v3 names removed from templates and global registrations; v5 names/directive registered globally; tag models are `string[]`; `showCloseIcon` dropped from the popovers. `vue-tsc --noEmit` and Encore build pass. Browser smoke: form layout, input, invalid state, float label, table, list, CRUD, messages and overlay routes render the expected v5 components; `v-mask` formats `12345678` → `12/34/5678` with invalid state preserved; both popovers open; no unresolved component/directive warnings (only pre-existing demo-data 404s).
  - Verify: build, typecheck; form/overlay smoke.
  - Files: `assets/app.ts`, `assets/views/uikit/*`, `assets/layout/AppConfig.vue`
  - Dependencies: Task 14
  - Scope: M

- [x] Task 16: Migrate removed table, panel, and list components
  - Acceptance: TriStateCheckbox→Checkbox indeterminate, DataViewLayoutOptions→SelectButton, AccordionTab→AccordionPanel structure, TabView/TabPanel→Tabs structure.
  - Verified 2026-11-24 in four slices (commits `9668ca6`, `75fa48e`, `503604c`): followed upstream Sakai 5 `TableDoc`, `ListDoc` and `PanelsDoc` structures. Table's Verified filter is a binary `Checkbox` with `indeterminate` bound to a null filter value; DataView layout uses `SelectButton` with grid/list icons and `:allowEmpty="false"`; accordion and tabs use the `AccordionPanel`/`AccordionHeader`/`AccordionContent` and `Tabs`/`TabList`/`Tab`/`TabPanels` trees. `vue-tsc --noEmit` and build pass. Browser smoke: Verified filter renders an indeterminate checkbox, DataView switches to `p-dataview-list`, accordion panels expand, and tabs switch content — all with no unresolved-component warnings.
  - Verify: typecheck; table/panel smoke.
  - Files: `assets/app.ts`, `assets/views/uikit/Table.vue`, `assets/views/uikit/List.vue`, `assets/views/uikit/Panels.vue`
  - Dependencies: Task 15
  - Scope: M

- [x] Task 17: Migrate removed/deprecated menu, media, and misc components
  - Acceptance: Steps→Stepper, TabMenu→Tabs, `v-badge`→OverlayBadge; MultiSelect→Select multiple; Galleria→Gallery; Image→Gallery; ColorPicker→InputColor; ScrollPanel→ScrollArea; Password→InputPassword; ImageCompare→Compare.
  - Verified 2026-11-24 in five slices (commits `c9c38b0`, `53c22ce`, `711e261`, `1c38e66`, `5080529`): mapped against the official PrimeVue 5 migration guide plus upstream Sakai 5 Menu/Misc/Media docs and official component docs (Stepper, Tabs, OverlayBadge, Select multiple, Gallery, InputColor, ScrollArea, InputPassword). Stepper/Tabs keep the nested-route integration; `v-badge` and the no-op badge directive are gone; MultiSelect is `Select multiple` everywhere; Galleria/Image use the compound Gallery (thumbnail strip and fullscreen single-image preview); ColorPicker/ScrollPanel/Password use InputColor/ScrollArea/InputPassword. No `ImageCompare` usage exists in this port. All removed component imports/registrations were deleted. `vue-tsc --noEmit` and build pass; browser smoke of menu, misc, input, invalid-state, table, media and login routes shows the expected v5 components with no unresolved-component/directive warnings.
  - Verify: build, typecheck; route smoke.
  - Files: `assets/app.ts`, `assets/views/uikit/Menu.vue`, `assets/views/uikit/Media.vue`, `assets/views/uikit/Misc.vue`, `assets/views/uikit/Input.vue`, `assets/views/uikit/InvalidState.vue`, `assets/views/uikit/FloatLabel.vue`, `assets/views/uikit/Table.vue`, `assets/views/pages/auth/Login.vue`
  - Dependencies: Task 16
  - Scope: M

- [x] Task 18: Replace deprecated core `Chart`
  - Acceptance: `assets/views/uikit/Chart.vue` and dashboard chart usage no longer import `primevue/chart`; direct Chart.js (typed) or removal is implemented; decision documented.
  - Decision 2026-11-24: **keep the charts using direct Chart.js** rather than removing them. The PRO replacement is out of scope for this Community port, and removing the demos/dashboard widget would lose existing functionality. `chart.js@4` and `chartjs-plugin-datalabels` are already dependencies. Added a typed `ChartCanvas` wrapper (`type: ChartType`, `data: ChartData`, `options?: ChartOptions`) that owns the Chart.js lifecycle and recreates the chart when props change; `assets/components/chart/chartjs.ts` registers `registerables` plus the datalabels plugin once. `app.ts` no longer imports/registers `primevue/chart` or sets up the plugin. `Chart.vue` also updates v3-era Chart.js option names (`fontColor`→`color`, `grid.drawBorder`→`border.display`) so the typed config is valid for v4.
  - Verified 2026-11-24: `vue-tsc --noEmit`, build and lint (0 errors) pass; browser smoke of `/` and `/uikit/charts` shows 1 + 6 painted canvases at non-zero dimensions, no `.p-chart` remnants and no console errors/deprecation warnings.
  - Verify: build, typecheck; chart route renders with no deprecation warnings.
  - Files: `assets/components/chart/ChartCanvas.vue`, `assets/components/chart/chartjs.ts`, `assets/views/uikit/Chart.vue`, `assets/components/dashboard/RevenueStreamWidget.vue`, `assets/app.ts`
  - Dependencies: Task 17
  - Scope: S

- [x] Task 19: Remove v3 theme/PrimeFlex imports and CSS classes
  - Acceptance: no `primevue/resources`, `theme.css`, `primeflex`, `.p-link`, `.p-highlight`, `.p-fluid`, or `inputStyle` remain; 16px root font size or compat preset chosen.
  - Decision 2026-11-24: **AuraCompat preset** remains the app preset (`app.ts`), satisfying the root-font-size/compat requirement; the Sakai `$scale: 14px` layout scale is kept for layout fidelity. The unused v3 theme bundle in `public/themes/**` (89 files incl. legacy fonts) was deleted along with the `<link id="theme-css">` in `templates/base.html.twig`; `primeflex` was uninstalled. Its remaining layout utilities were converted in place rather than reimplemented: `grid`/`col-*` → Tailwind `grid grid-cols-12`/`col-span-*`, `field`/`formgrid`/`formgroup-inline` → Tailwind spacing/flex utilities, `p-fluid` → `w-full [&_.p-component]:w-full`, `p-link` → Tailwind button reset, and v3 helpers (`text-900`, `surface-card`, `align-items-center`, `w-9rem`, …) → Tailwind/PrimeUI utilities. Legacy v3 CSS variables were replaced with v4 design tokens; page/card surfaces now use `--p-content-background`, borders `--p-content-border-color`, headings `--p-text-color` and hovers `--p-button-secondary-hover-background` so they flip correctly in dark mode. The stale `primevue` layer was dropped from both `tailwind.css` and the PrimeVue `cssLayer.order`.
  - Verified 2026-11-24: scoped `git grep -E 'primeflex|theme\.css|p-fluid|inputStyle|p-link|p-highlight|primevue/resources'` over `assets/`, `templates/`, `package*.json`, `webpack.config.js` is clean (only historical mentions remain in `docs/` and this checklist). `vue-tsc --noEmit`, build, lint (0 errors), PHPUnit smoke pass. Browser smoke from `/` across form layout, message, list, table, charts, misc, media and login shows no legacy layout classes, no theme stylesheet request, painted charts, correct 12-column grids, 9rem inline labels, and light/dark surface + text flipping.
  - Note: the literal repo-wide grep is not clean because `docs/analysis-lamiral-sakai-upgrade.md` and this task history describe the removed v3 APIs.
  - Verify: `git grep -E 'primeflex|theme\.css|p-fluid|inputStyle'` clean; visual smoke.
  - Files: `assets/assets/**`, `assets/**/*.vue`, `templates/base.html.twig`, `package.json`, `package-lock.json`, `public/themes/**` (deleted)
  - Dependencies: Task 18
  - Scope: S

**Checkpoint: PrimeVue 5**
- [x] No removed v3 APIs or v3 theme imports remain
- [x] No PRO component required; no license notice (verified with the Community key in `.env.local`)

## Phase 4 — Lamiral Tier A reuse

- [x] Task 20: Add typed fetch wrapper and API types
  - Acceptance: `fetch.ts` supports single-flight refresh, CSRF cookie header, merge-patch, typed errors, blob downloads, configurable endpoints; DTOs in `assets/types/api.ts`.
  - Decision 2026-11-24: ported the Lamiral pattern as a **framework-agnostic `FetchClient`** — auth storage, refresh/login/logout URLs, CSRF cookie name, fetch implementation and the unauthorized hook are injected, so Pinia/router stay out of the wrapper (Task 22 supplies those hooks). `baseUrl` plus `endpoints` are configurable; absolute URLs are passed through. Includes JSON verbs, `application/merge-patch+json` for PATCH, typed `ApiError` extraction (`errors`/`detail`/`hydra:description`/`message`/plain text), 204 → `null`, `download()` returning a `Blob` and `saveBlob()`/filename helpers, proactive expiry refresh (60s skew), one-shot 401 refresh retry, and a shared in-flight refresh promise for concurrent 401s. `assets/types/api.ts` holds type-only DTOs (auth, JWT, Hydra/plain collections, demo entities).
  - Verified 2026-11-24: 11 RED-first `node:test` cases in `tests/frontend/fetch.test.cjs` pass via `npm run test:fetch` (compiles the wrapper with `tsc` into gitignored `var/test-build/`; no new test dependency). `npm run typecheck`, build, lint (0 errors, 2673 pre-existing formatter warnings) pass.
  - Verify: typecheck; manual request smoke.
  - Files: `assets/utils/fetch.ts`, `assets/types/api.ts`, `tests/frontend/fetch.test.cjs`, `package.json`
  - Dependencies: Task 7
  - Scope: M

- [x] Task 21: Replace mock services with typed API services
  - Acceptance: service pattern ported without Lamiral-domain URLs; static demo services removed or clearly marked demo-only.
  - Decision 2026-11-24: all five services now extend a typed `ApiService<T>` (`assets/service/api.ts`) backed by the Task 20 `FetchClient`; the base URL comes from an optional `window.API_BASE_URL` (default `/api`). The old direct `fetch('demo/data/*.json')` calls and the remote `primefaces.org` customer URL are gone. `DemoDataController` serves the existing Sakai fixtures through a local, whitelisted `/api/demo/{fixture}` GET endpoint; fixtures remain demo-only data but are no longer fetched as static files by the frontend. Services re-export their DTOs so existing views keep their imports.
  - Verified 2026-11-24: RED-first PHPUnit coverage (`DemoDataControllerTest`) proves the endpoint returns `{"data": [...]}` and rejects unknown fixtures; three RED-first `node:test` service cases prove local URLs, Hydra/plain/array normalisation and the typed CRUD pattern (`npm run test:fetch`, 14 tests). Build, typecheck, lint (0 errors), full PHPUnit (3 tests) and a browser smoke of `/pages/crud` pass: the page requests `/api/demo/products` and renders 30 products with no console errors.
  - Verify: build; one API-backed route returns data.
  - Files: `assets/service/*.ts`, `assets/types/api.ts`, `assets/types/window.d.ts`, `src/Controller/DemoDataController.php`, `tests/Functional/DemoDataControllerTest.php`, `tests/frontend/services.test.cjs`, `package.json`
  - Dependencies: Task 20
  - Scope: M

- [x] Task 22: Add Pinia stores
  - Acceptance: `pinia.ts` installed in `app.ts`; `auth.ts` typed with roles and `localStorage` token; token storage decision documented.
  - Decision 2026-11-24: `assets/store/pinia.ts` exports a single shared Pinia instance; `app.ts` installs it (instead of an inline `createPinia()`) and wires the Task 20 client with `apiClient.setAuthHooks()` — token get/set, refreshed payload and unauthorized logout all delegate to the typed `useAuthStore(pinia)`. `FetchClient` auth callbacks are late-bound via a new `setAuthHooks()` method, keeping the wrapper framework-agnostic. The store ports Lamiral's role model (`ROLE_ADMIN`, `ROLE_CASHIER`, `ROLE_USER`, `ROLE_CUSTOMER`, admin-authorises-all, isStaff/isCustomer), stores token/roles/user as JSON in `localStorage` and clears them on logout; the refresh token remains in an httpOnly Symfony cookie (backend in Tasks 25–28). `apiClient` now uses base-URL-relative auth endpoints so `/api/login` is not double-prefixed.
  - Token storage decision / XSS risk: an access token in `localStorage` is readable by any XSS in the SPA; this is accepted for now (matching Lamiral Tier A) because no auth backend or refresh rotation exists yet. Mitigations to revisit in Task 28: strict CSP, no inline scripts, vetted dependencies, short JWT lifetime and rotating refresh cookies. Do not move the token into an httpOnly cookie without revisiting CSRF protection for API calls.
  - Verified 2026-11-24: RED-first tests — three `node:test` store cases (login persistence/roles/admin authorisation, local-only logout, restore-from-storage) plus a late-bound-hook case; `npm run test:fetch` 18/18 pass. `npm run typecheck`, build, lint (0 errors, 2588 pre-existing formatter warnings) and PHPUnit (3 tests) pass. Browser smoke: SPA login shell renders (Email/Password/Remember me/Sign In, no console errors) and, after navigating back, `/pages/crud` still loads rows via `/api/demo/products` with no console errors.
  - Verify: typecheck; login/logout shell smoke.
  - Files: `assets/store/pinia.ts`, `assets/store/auth.ts`, `assets/app.ts`, `assets/utils/fetch.ts`, `assets/service/api.ts`, `tests/frontend/auth-store.test.cjs`, `tests/frontend/fetch.test.cjs`, `package.json`
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
