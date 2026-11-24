# Analysis: Lamiral reuse, PrimeVue/Sakai upgrade, and TypeScript conversion

- **Date:** 2026-11-24
- **Status:** Analysis complete; decisions confirmed 2026-11-24 (see `docs/adr/0002-primevue-version-and-sakai-strategy.md`)
- **Scope:** Analysis and documentation only. No application code, dependencies, or infrastructure were changed.

## 1. Method

The two local repositories were compared read-only:

- This project: `/home/nadim/Projects/Symfony/SymfonyFrankenPHP` (`origin/main` at `84550eb`)
- Lamiral: `/home/nadim/Projects/Lamiral/LamiralApp` (`origin/main` at `6df3d85`, `git@github.com:nadimattari/lamiral.git`)

Official sources were used for version and migration facts (see [Sources](#10-sources)).

## 2. Stack snapshot

| Area          | This project (current)          | Lamiral                            | Upstream Sakai Vue 5.0.0                            |
|---------------|---------------------------------|------------------------------------|-----------------------------------------------------|
| PHP / Symfony | 8.2.35 / Symfony 7.4.21         | 8.2.35 / Symfony 7.4.x             | n/a                                                 |
| Bundler       | Webpack Encore 4.6.1            | Webpack Encore 3.1.x               | Vite 5                                              |
| Vue           | 3.4.21 (locked)                 | ^3.4                               | ^3.4.34                                             |
| PrimeVue      | **3.51.0**                      | **3.51.0**                         | **^4.5.4**                                          |
| PrimeFlex     | 3.3.1                           | 3.3.1                              | not used (Tailwind 4)                               |
| Theming       | v3 `theme.css` / SCSS layout    | v3 `theme.css` / SCSS layout       | `@primeuix/themes` ^2.0.0                           |
| CSS utility   | PrimeFlex 3 + hand-written SCSS | PrimeFlex 3 + hand-written SCSS    | Tailwind CSS 4 + `tailwindcss-primeui`              |
| State         | none (no Pinia)                 | Pinia 4.1                          | none                                                |
| TS tooling    | TS 5.4.5, `tsc --noEmit` only   | Same config as this repo           | JS only (`main.js`, `layout.js`, `router/index.js`) |
| Auth          | none                            | Lexik JWT + refresh tokens + roles | none                                                |
| Mail/report   | Symfony Mailer only             | MailRun/EmailLog/report services   | none                                                |

Key baseline finding: this repository has **no `.ts` files**, `tsconfig.json` sets `allowJs: true`, and the typecheck script runs `tsc --noEmit`. Since `tsc` does not parse `.vue` SFC contents, the current "typecheck" is effectively a no-op. Vue's official guidance is to use `vue-tsc` for SFC checking. [Sources](#10-sources)

## 3. What can be picked from Lamiral

### 3.1 Tier A — frontend infrastructure (recommended, low coupling)

These files are mostly self-contained and can be adapted without changing the database or Symfony security model.

| Lamiral file(s)                                                                                              | Value                                                                                                                                          | Prerequisites / adaptation                                                                                                                                       | Recommendation                                                                        |
|--------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------|
| `assets/utils/fetch.js`                                                                                      | Auth-aware `fetch` wrapper: JSON/PATCH/merge-patch, 401 refresh with single-flight, CSRF cookie header, error extraction, blob download helper | Imports `useAuthStore` and `router`; expects `/api/login`, `/api/token/refresh`, `/api/logout` and a `csrf` cookie. Generalize endpoints/options before copying. | **Port, as TS, parameterized.** Highest-value reusable code in Lamiral.               |
| `assets/utils/common.js`                                                                                     | `formatNumber`, `formatDate`                                                                                                                   | Hard-coded `Indian/Mauritius` timezone and `en-EN`/`en-GB` locales. Make locale/timezone parameters or config.                                                   | Port as TS; small.                                                                    |
| `assets/service/*.js` (`customers`, `products`, `sales`, `users`, `contacts`, `portal`, `mail`, `reference`) | Consistent CRUD/service pattern, `URLSearchParams`, promise caching (`reference.js`, `mail.js`)                                                | Endpoints are Lamiral-domain. Reuse the pattern, not the literal URLs.                                                                                           | Port the pattern; replace current Sakai mock services with API-backed typed services. |
| `assets/store/pinia.js`                                                                                      | Pinia instance                                                                                                                                 | Add `pinia` dependency; register in app entry.                                                                                                                   | Port.                                                                                 |
| `assets/store/auth.js`                                                                                       | Roles, login/logout, localStorage persistence, `isAuthorisedFor`                                                                               | Depends on `AuthService` and the fetch wrapper. Access token in `localStorage` is XSS-exposed; decision confirms keeping it with an httpOnly refresh cookie.     | Port as typed TS; document XSS risk acceptance.                                       |
| `assets/store/portal.js`                                                                                     | Profile loading/caching                                                                                                                        | Lamiral-domain (`/api/portal/me`).                                                                                                                               | Skip unless portal feature is in scope.                                               |
| `assets/utils/portal.js`                                                                                     | Meal/status labels, money/date/quantity formatting                                                                                             | Domain-specific.                                                                                                                                                 | Skip unless portal feature is in scope.                                               |

### 3.2 Tier B — authentication backend (in scope, medium/high effort)

Porting these creates a real user model and API security layer in a starter that currently has only `Kernel.php` and `AppController.php`.

| Lamiral piece                                                                                    | Dependencies                                                          | Notes                                                                                                                |
|--------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|
| `src/Service/Auth/RefreshTokenService.php`                                                       | `RefreshToken` entity + repository, `User` entity, Doctrine migration | Good design: SHA-256 hashed tokens, rotation in one flush, revoke-all, 30-day TTL. Worth porting if auth is desired. |
| `src/Security/*`, `src/EventListener/AuthenticationSuccessListener.php`                          | Lexik JWT bundle, security config                                     | Needed for the cookie/CSRF flow used by `fetch.js`.                                                                  |
| `src/Controller/AuthController.php`, `TokenController.php`                                       | JWT, refresh service, CSRF cookie                                     | Provides `/api/login`, `/api/token/refresh`, `/api/logout`.                                                          |
| `config/packages/security.yaml`, `config/packages/lexik_jwt_authentication.yaml`, `config/jwt/*` | Composer package `lexik/jwt-authentication-bundle`                    | Keys must be generated per environment and never committed.                                                          |
| `config/packages/api_platform.yaml` hardening                                                    | API Platform 4                                                        | Lamiral disables docs in prod/test and enables only in dev; useful regardless of auth.                               |

### 3.3 Mail backend + out-of-scope report layers

The **mail backend is in scope** (MailRouter, MailRun lifecycle/dispatcher, mail settings endpoint, Mailpit in dev). The report/invoice layers it depends on are **out of scope** except where minimally required to make mail compile and send a simple test message; they must not be ported wholesale.

| Lamiral piece                                                                                | Dependencies                                                                                                    | Notes                                                                                                                       |
|----------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| `src/Service/Mail/MailRouter.php`                                                            | Env params `MAIL_FROM`, `MAIL_REPLY_TO`, `MAIL_REDIRECT_TO`, `MAIL_FACTORS_RECIPIENTS`, `MAIL_THROTTLE_SECONDS` | **Reusable pattern** (test redirect, `[TEST]` subject rewrite, `X-Original-Recipient`). Port this if report mail is wanted. |
| `src/Service/Mail/MailRunManager.php`, `MailRunDispatcher.php`, `ActiveMailRunException.php` | `MailRun` entity/repository, `symfony/process`, console commands, detached workers                              | Complex lifecycle; only port if queued report sends are required.                                                           |
| `src/Service/Mail/ReportMailer.php`                                                          | PhpSpreadsheet, report services, Twig email templates                                                           | Lamiral-domain (statements, factors, XLSX attachments).                                                                     |
| `src/Service/Report/*`, `src/Service/Invoice/*`, `src/Service/Pricing/*`                     | `phpoffice/phpspreadsheet`, `dompdf/dompdf`, Dropbox sync, etc.                                                 | Do not port for a generic starter.                                                                                          |

### 3.4 Do not copy verbatim from Lamiral

- `assets/app.js` — older than this repo's copy; it lacks the ChartDataLabels registration already fixed here, and is written against PrimeVue 3 component names.
- `assets/layout/composables/layout.js` and layout SFCs — older formatting/feature level; this repo's Sakai-derived layout is newer in some places.
- `eslint.config.mjs`, `tsconfig.json` — byte-for-byte equivalent to this repo's; no benefit.
- `assets/service/{CustomerService,CountryService,NodeService,PhotoService,ProductService}.js` — static demo data; replace with typed API services instead.

## 4. PrimeVue upgrade

### 4.1 Version facts

- Current: **PrimeVue 3.51.0** (locked), PrimeFlex **3.3.1**.
- **PrimeVue 4.5.4** is MIT-licensed and is the version used by upstream **Sakai Vue 5.0.0**. It is the natural first target.
- **PrimeVue 5.0.5** is the current latest. The official v5 migration guide says there are **no breaking changes to public component APIs** from v4, but:
  - v5 is under the **PrimeUI Community/Commercial license**, not MIT. All existing MIT versions remain MIT forever.
  - Many v3/v4-era APIs are removed or deprecated (see below).
  - `Chart` is deprecated in v5 in favor of PrimeUI PRO Charts (commercial), which affects the current `views/uikit/Chart.vue` and Sakai dashboard demos.
- PrimeFlex **3 is not compatible with PrimeVue 4+**; upgrade to PrimeFlex 4 or move to Tailwind.

**Decision (2026-11-24):** a **PrimeUI Community License** is held. Official terms confirm it includes the *same core library features* as Commercial (full PrimeVue, PrimeReact, PrimeNG, PrimeIcons), so no core components are missing. It excludes only PrimeUI PRO components (Scheduler, Charts, Task Board, Text Editor, Diagram), PrimeBlocks, Theme Designer, and premium support. Community keys are annual (12 months), renewable while eligible, verified offline, and the key may appear in the app bundle; it must not be published for others to reuse. Therefore **target PrimeVue 5.0.5 directly**. The two core `Chart` demo usages will be replaced with direct Chart.js, since core `Chart` is deprecated in v5 in favour of the excluded PRO Charts. The held key has 4 seats, was created 2026-11-14, and is valid until 2027-11-14.

### 4.2 Migration surface

The current app is a PrimeVue 3 Sakai fork. At least 55 usages of v3-era components exist in `assets/` (counted via grep). Official mapping:

| v3 / early v4 API                                 | Target                                                    | Status                                                    |
|---------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| `Calendar`                                        | `DatePicker`                                              | Removed in v5                                             |
| `Dropdown`                                        | `Select`                                                  | Removed in v5                                             |
| `InputSwitch`                                     | `ToggleSwitch`                                            | Removed in v5                                             |
| `OverlayPanel`                                    | `Popover`                                                 | Removed in v5                                             |
| `Sidebar`                                         | `Drawer`                                                  | Renamed in v4                                             |
| `TabView` / `TabPanel`                            | `Tabs` / `TabList` / `Tab` / `TabPanels`                  | Removed in v5                                             |
| `AccordionTab`                                    | `AccordionPanel` + `AccordionHeader` + `AccordionContent` | Removed in v5                                             |
| `BadgeDirective` (`v-badge`)                      | `OverlayBadge`                                            | Removed in v4                                             |
| `Chips`                                           | `AutoComplete multiple` (v4) or `InputTags` (v5)          | Removed in v5                                             |
| `InlineMessage`                                   | `Message`                                                 | Removed in v4/v5                                          |
| `TabMenu`, `Steps`                                | `Tabs`, `Stepper`                                         | Removed in v5                                             |
| `TriStateCheckbox`                                | `Checkbox` with indeterminate                             | Removed in v4                                             |
| `DataViewLayoutOptions`                           | `SelectButton`                                            | Removed in v4                                             |
| `InputMask` component                             | `Mask` directive                                          | Deprecated/removed in v5                                  |
| `Password`                                        | `InputPassword`                                           | Deprecated in v5                                          |
| `Galleria`, `Image`, `ColorPicker`, `ScrollPanel` | `Gallery`, `InputColor`, `ScrollArea`                     | Deprecated in v5                                          |
| `MultiSelect`                                     | `Select multiple`                                         | Deprecated in v5                                          |
| `primevue/api` imports                            | `@primevue/core/api`                                      | Relocated in v4                                           |
| v3 `theme.css`, `primevue/resources`              | `@primeuix/themes` presets                                | Removed in v4                                             |
| `inputStyle` config                               | `inputVariant`                                            | Removed in v4                                             |
| PrimeFlex 3                                       | Tailwind 4 (PrimeFlex removed)                            | Chosen CSS pipeline; PrimeFlex 3 is incompatible with v4+ |

Also note the v4 CSS class removals (`.p-link`, `.p-highlight`, `.p-fluid`) and the v5 16px root-font-size assumption (compat variants exist for 14px layouts).

### 4.3 Recommendation

1. Migrate **directly to PrimeVue 5.0.5** with the held Community License. v5 has no breaking public API changes from v4, so the work is the single v3→v5 rename/removal/theming migration — doing v4 first would duplicate it.
2. Use **Tailwind 4 + `tailwindcss-primeui` + `@primeuix/themes`** and remove PrimeFlex, matching upstream Sakai's utility approach while keeping Encore.
3. Replace the deprecated core `Chart` component with direct Chart.js in the two demo views; do not buy PRO Charts for a starter template.
4. Wire the license key from `PRIMEUI_LICENSE_KEY` (never committed) via `app.use(PrimeVue, { license })`; track annual renewal.

MIT-licensed PrimeVue 4.5.x remains the fallback if the Community license lapses or eligibility is lost.

## 5. Sakai Vue upgrade

### 5.1 Upstream facts

The current `primefaces/sakai-vue` repository has tags `3.x`, `4.0.0`–`4.3.0`, and `5.0.0`; branches are `master` and `prod`, both at the 5.0.0 commit.

Verified 2026-11-24: `git ls-remote https://github.com/primefaces/sakai-vue.git refs/tags/5.0.0^{}` resolves to `ec6b6ef53ad4fa7f2861277f50f2d9e236c40372`. A pristine reference can be fetched reproducibly with:

```bash
mkdir -p /tmp/opencode/sakai-vue-5.0.0
curl -sSL https://github.com/primefaces/sakai-vue/archive/refs/tags/5.0.0.tar.gz \
  | tar -xz --strip-components=1 -C /tmp/opencode/sakai-vue-5.0.0
```

Upstream 5.0.0 is still **JavaScript**, not TypeScript:

- `src/main.js`, `src/router/index.js`, `src/layout/composables/layout.js`
- `src/service/*.js`
- `.vue` files without `lang="ts"`

It is also a **Vite + Tailwind 4** project:

- `vite.config.mjs`, `@vitejs/plugin-vue`
- `@tailwindcss/vite`, `tailwindcss@^4.1.17`, `tailwindcss-primeui@^0.6.0`
- `@primevue/auto-import-resolver`, `unplugin-vue-components`

This project deliberately keeps Webpack Encore (see `docs/adr/0001-bundler.md`), so upstream cannot be dropped in unchanged.

### 5.2 Two viable strategies

|              | **A. Rebase/port upstream Sakai 5.0.0**                                                                                                | **B. Upgrade the current Encore Sakai fork in place**                                 |
|--------------|----------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------|
| What happens | Vendor 5.0.0 as a reference, port `src/` to `assets/`, JS → TS, replace Vite auto-import with explicit imports, adapt Tailwind or SCSS | Keep current file layout/SCSS, apply PrimeVue 4→5 migration directly to existing SFCs |
| Pros         | Gets current upstream layout/components; clear upstream sync point; TS from day one                                                    | Smaller diff; preserves existing Encore/SCSS setup; lower regression risk             |
| Cons         | Large port; CSS pipeline decision (Tailwind vs SCSS) is unavoidable; future upstream merges still need care                            | Does not deliver new upstream Sakai 5 UI; keeps an old fork shape                     |
| Effort       | Large                                                                                                                                  | Medium–Large                                                                          |

### 5.3 Recommendation

**Strategy A is chosen.** Guardrails:

- Pin upstream tag `5.0.0` in a pristine reference location (commit `ec6b6ef...`) so future merges are possible; never edit the reference.
- Keep Encore; do **not** adopt Vite in this change.
- **CSS pipeline: option (a)** — enable PostCSS in Encore, add `tailwindcss@4`, `@tailwindcss/postcss`, and `tailwindcss-primeui`, and keep upstream utility classes. Do not translate them into SCSS; remove PrimeFlex and the old SCSS utility layer where Tailwind replaces it.
- Replace Vite `import.meta.env` with Encore-compatible globals exposed from Twig.
- Use explicit component imports in `assets/app.ts` rather than adding `unplugin-vue-components` unless auto-import becomes a stated requirement.
- Align PrimeVue `darkModeSelector` with Tailwind's dark variant per the official PrimeVue/Tailwind guide.

## 6. JavaScript → TypeScript conversion

### 6.1 Is it possible?

Yes. Vue 3 has first-class TS support, and Encore already has `.enableTypeScriptLoader()` enabled in `webpack.config.js`. The blockers are tooling and discipline, not framework support.

However:

- Add `vue-tsc` and change `typecheck` to `vue-tsc --noEmit`; plain `tsc` cannot check `.vue` SFCs.
- Add TS-aware ESLint parser/plugin (`@typescript-eslint/parser` + plugin) to `eslint.config.mjs`; the current flat config only enables Vue and Prettier rules.
- Declare `eslint-plugin-prettier` as a direct devDependency (it is currently only transitively present).
- Add an SFC shim if needed by the chosen toolchain (`declare module '*.vue'`), though `vue-tsc` generally handles SFCs directly.

### 6.2 Conversion approach

1. **New and ported files are TS by default.** `assets/app.ts`, `assets/router/index.ts`, `assets/service/*.ts`, `assets/store/*.ts`, `assets/utils/*.ts`, `assets/layout/composables/layout.ts`.
2. **SFCs:** add `<script setup lang="ts">`, type props with `defineProps<T>()` / `withDefaults`, emits with `defineEmits<{...}>()`, refs with `ref<T>()`, and event handlers with explicit `Event`/`MouseEvent` types. This follows the official Vue Composition API TS guidance.
3. **Domain types:** introduce `assets/types/api.ts` (or generated types from API Platform OpenAPI) for entities, collections, and auth payloads. Avoid `any`; use `unknown` plus narrowing at the API boundary.
4. **Strict mode:** keep `strict: true`; do not add `// @ts-ignore` as an escape hatch. If a PrimeVue type is wrong, isolate the cast in one adapter and document why.
5. **Sweep order:** shared infra → layout shell → routes/views → demo/uikit pages. Demo pages can be converted last or deleted if they are not part of the product surface.

### 6.3 Caveats

- Upstream Sakai 5 is JS, so a full TS conversion creates a local fork. Keep the pristine reference and a documented patch strategy.
- Vue's docs note that `ts-loader` type-checking inside the webpack transform pipeline is not a clean solution; use `vue-tsc` as a separate check, which this repo can do without changing bundlers.
- PrimeVue 5's `Chart` deprecation means the chart demo needs a decision: keep PrimeVue 4's `Chart`, move to plain Chart.js, or buy PRO Charts.

## 7. Proposed delivery plan

See `tasks/plan-sakai-primevue-ts.md` and `tasks/todo-sakai-primevue-ts.md`. The work is sliced into small, verifiable tasks with checkpoints after each phase.

## 8. Risks

| Risk                                            | Impact | Mitigation                                                                                                        |
|-------------------------------------------------|--------|-------------------------------------------------------------------------------------------------------------------|
| Community license expires / eligibility lost    | Medium | Track annual renewal; MIT PrimeVue 4.5.x remains the fallback.                                                    |
| License key missing in an environment           | Medium | Inject from `PRIMEUI_LICENSE_KEY`; verify in each environment; a missing key may show a license notice.           |
| Big-bang UI regression                          | High   | Port one route/layout slice at a time; visual smoke after each checkpoint.                                        |
| Tailwind/PrimeVue cascade or dark-mode mismatch | Medium | Follow official PrimeVue Tailwind guide (layer order, `darkModeSelector` alignment); visual smoke.                |
| Future Sakai upstream merges become painful     | Medium | Pin tag/commit, keep reference copy, document local patches.                                                      |
| Auth token storage (localStorage)               | Medium | **Accepted risk** per decision 6; mitigate with short TTL, httpOnly refresh cookie, CSRF cookie, and XSS hygiene. |
| `vue-tsc` strictness on all SFCs                | Medium | Convert incrementally per route; `vue-tsc` is a required gate at each checkpoint.                                 |
| Encore remains in maintenance mode              | Low    | Already accepted in ADR 0001; revisit only with Symfony 8/PHP 8.4.                                                |
| No frontend test suite                          | Medium | At minimum add build + `vue-tsc` + lint + browser smoke checks per checkpoint.                                    |

## 9. Confirmed decisions (2026-11-24)

1. **PrimeVue version:** **5.0.5** with the held **PrimeUI Community License**. No essential core components are missing; PRO components are not needed. MIT 4.5.x is the fallback.
2. **Sakai strategy:** **Strategy A** — port upstream 5.0.0 (`ec6b6ef...`) onto Encore.
3. **CSS pipeline:** **Tailwind 4 via PostCSS in Encore** + `tailwindcss-primeui` + `@primeuix/themes`; remove PrimeFlex rather than translating utilities into SCSS.
4. **Lamiral scope:** **Tier A frontend infrastructure + auth backend + mail backend only**. No report/invoice/pricing/Dropbox layers.
5. **TypeScript depth:** **all SFCs and JS modules** converted to TypeScript, including demo/uikit pages.
6. **Auth token storage:** keep Lamiral-style **`localStorage` access token + httpOnly refresh cookie** (accepted XSS trade-off), with CSRF cookie for mutations.

## 10. Sources

Official documentation / upstream sources used for version-specific claims:

- PrimeVue v4 migration guide: <https://primevue.org/migration/v4/>
- PrimeVue v5 migration guide: <https://primevue.org/migration/v5/>
- PrimeUI licensing announcement: <https://primeui.dev/nextchapter>
- PrimeUI Community License summary: <https://primeui.dev/licenses/community>
- PrimeUI plan comparison and PRO component list: <https://primeui.dev/pricing>, <https://primeuipro.dev/>
- PrimeVue configuration / license key option: <https://primevue.org/configuration/#license>, <https://primevue.org/llms/pages/configuration.md>
- Tailwind 4 PostCSS installation: <https://tailwindcss.com/docs/installation/using-postcss>
- Encore PostCSS loader: <https://symfony.com/doc/current/frontend/encore/postcss.html>
- PrimeVue + Tailwind integration (plugin, layers, dark mode): <https://primevue.org/tailwind/>
- Sakai Vue repository (tags/branches/package.json/tree): <https://github.com/primefaces/sakai-vue>
- Sakai Vue 5.0.0 `package.json`: <https://raw.githubusercontent.com/primefaces/sakai-vue/master/package.json>
- PrimeVue npm metadata (4.5.4 / 5.0.5): <https://registry.npmjs.org/primevue/4.5.4>, <https://registry.npmjs.org/primevue/latest>
- PrimeFlex npm metadata: <https://registry.npmjs.org/primeflex/latest>
- Vue TypeScript overview (use `vue-tsc` for SFCs): <https://vuejs.org/guide/typescript/overview.html>
- Vue TS with Composition API (`defineProps`/`defineEmits` typing): <https://vuejs.org/guide/typescript/composition-api.html>
- Symfony Encore TypeScript setup: <https://symfony.com/doc/current/frontend/encore/typescript.html>

Local sources: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `webpack.config.js`, `tsconfig.json`, `eslint.config.mjs`, `assets/**`, `config/**` in both repositories.
