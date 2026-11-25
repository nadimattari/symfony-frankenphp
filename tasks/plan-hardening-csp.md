# Implementation Plan: CSP headers (`csp-headers` module)

Status: **proposed** (2026-11-25) — awaiting human review

Source spec: [SPEC-csp-headers.md](../spec/SPEC-csp-headers.md) · [capability map](../spec/capability-map.md) · [ADR 0003](../docs/adr/0003-auth-architecture.md)

Tasks are recorded in [todo-hardening-csp.md](todo-hardening-csp.md). These are dedicated files: `tasks/plan.md`, `tasks/plan-hardening-1.0.md`, `tasks/todo-sakai-primevue-ts.md` and its deliberately open Task 35 (November 2027) are untouched.

## Overview

Implement the approved `csp-headers` module after the completed `auth-rate-limiting` module. A Symfony subscriber generates a fresh base64url nonce per request, stores it on the Request, and emits the exact approved `Content-Security-Policy` on the response. The single inline Twig script receives that nonce; external Encore assets and dynamic chunks are covered by `'self'`. Demo views that currently load remote media are switched to local assets so the strict policy does not break them. Work is RED-first, one reviewable commit per task, and ends with a local fast-forward merge only after human review. No push.

Every task must also clear the standing [Definition of Done](../.opencode/skills/planning-and-task-breakdown/references/shared/definition-of-done.md): RED-first coverage for new behaviour, existing tests green, runtime verification, scoped changes, documentation, no secrets, human approval before merge.

## Architecture decisions

- **Nonce transport:** `kernel.request` generates 18 random bytes (`random_bytes`), base64url-encodes them (24 characters / 144 bits) and writes them to the `csp_nonce` Request attribute. `kernel.response` reads the same attribute and interpolates it into the header. No service holds request state, so FrankenPHP worker-mode reuse is safe. Verified `app.request` exists in Symfony 7.4 (`vendor/symfony/twig-bridge/AppVariable.php:98-105`), so Twig can read the attribute without a new global or service.
- **Subrequests:** the nonce is generated only for the main request and copied to subrequests, which need it if they render a template. The header is set only on main-request responses; this app has no template-rendering subrequests today.
- **Header scope:** set on every main response (including JSON API responses, which are unaffected). No content-type branching. In debug mode Symfony's `ErrorListener` removes the header from exception responses (`vendor/symfony/http-kernel/EventListener/ErrorListener.php:120-130`); in `dev` WebProfilerBundle adds its own nonces and `'unsafe-inline'` (`ContentSecurityPolicyHandler.php:114-154`). Functional tests run with `APP_ENV=test` and the toolbar off, so they assert the exact enforced policy; a prod-like smoke confirms it outside `dev`.
- **Scripts:** keep `'self'` in `script-src`. Encore emits external same-origin tags (`runtime.js`, `vendors-*.js`, `app.js`) and injects dynamic chunks as external same-origin `<script>` tags, all covered by `'self'`. No `__webpack_nonce__` build-time change is needed. Vue is built runtime-only (`webpack.config.js:78`, `runtimeCompilerBuild: false`); app source has no `eval`, `new Function` or string-argument timers. The webpack global fallback at `public/build/runtime.js:122-129` returns `globalThis` first, so the `new Function('return this')()` branch is unreachable in supported browsers. No `'unsafe-eval'`.
- **Styles:** keep `style-src 'self' 'unsafe-inline'` as the approved residual (170 inline `style` attributes, `:style` bindings and PrimeVue's injected `<style>` tags). Do not wire PrimeVue `csp.nonce` in V1 — it would not tighten the policy while `'unsafe-inline'` remains.
- **Demo media:** replace the four `primefaces.org` image URLs with existing local files (`/demo/images/product/${item.image}`, `/demo/images/avatar/${...}`) and the YouTube `<iframe>` with an external documentation link. Local fixtures already contain matching filenames; no new binary assets or remote allowlist.
- **Policy location:** keep the policy string as a private constant in the subscriber. No `config/services.yaml` change is needed — `_defaults.autoconfigure: true` already registers `EventSubscriberInterface` implementations.
- **Increment discipline:** each task is one commit. Tests are RED before the first production change and GREEN before the next slice. Verification-only tasks produce no code changes.
- **Branching:** work on `feature/csp-headers`, created from local `main` at `d1ddcab` (12 commits ahead of `origin/main`, none pushed). No force-push, history rewrite or push without explicit approval.

## Task list

### Phase 1: RED and the control itself

- [x] C1 — Record approved spec and create the working branch (`9f33c2b`)
- [ ] C2 — RED: add `tests/Functional/CspHeadersTest.php`
- [ ] C3 — Emit the nonce-based CSP and nonce the Twig globals script

### Checkpoint: CSP core green

- [ ] Focused `CspHeadersTest.php` fails before C3 and passes after it
- [ ] Full `simple-phpunit` suite passes with no regressions
- [ ] `curl -sSI http://localhost:8180/` shows the exact approved policy

### Phase 2: UI compatibility

- [ ] C4 — Replace remote demo media with local assets
- [ ] C5 — Rebuild assets and run a browser smoke for zero CSP violations

### Checkpoint: UI compatible

- [ ] `npm run build`, `npm test`, `npm run lint`, `npm run typecheck` pass
- [ ] Login, a dynamically loaded route and all touched demo views render with no CSP console violations
- [ ] No remote host was added to any CSP directive

### Phase 3: Close the module

- [ ] C6 — Update ADR 0003 and README; run full gates and secret checks
- [ ] C7 — Merge the increment into local `main` (fast-forward, no push)

### Checkpoint: CSP module merged

- [ ] Full PHPUnit suite green; no secret or raw credential in the diff
- [ ] Human review of the merged diff and explicit push decision
- [ ] `hardening-release` starts only after this checkpoint is approved

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Nonce missing on a response (e.g. error/subrequest) blocks SPA scripts | High | C2 asserts header and rendered nonce on the same response; subscriber copies master nonce to subrequests; debug error pages are explicitly out of the test matrix and documented |
| Dev toolbar adds `'unsafe-inline'`, hiding a bad policy | Medium | Assert exact directives in `APP_ENV=test` (toolbar off); verify enforced header in prod-like smoke |
| Strict `default-src 'self'` breaks demo images/embed | Medium | C4 replaces all four remote images and the iframe with local content/external link; C5 browser smoke covers those views |
| `'unsafe-eval'` appears necessary from webpack runtime | High | Source-verified `globalThis` short-circuit; C2 asserts its absence; browser smoke exercises a dynamic chunk route |
| Worker-mode nonce reuse across requests | High | Per-request Request attribute, no mutable service state; C2 asserts nonce rotation |
| `localStorage` token remains XSS-readable even with CSP | Accepted | Documented in ADR 0003; moving it needs a new ADR and is out of scope |

## Open questions

None blocking. The two spec questions were approved with their recommended defaults: WebProfiler's dev-only `'unsafe-inline'` addition is accepted, and the header is emitted on all responses.
