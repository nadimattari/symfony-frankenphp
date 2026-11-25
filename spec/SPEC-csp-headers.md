# Spec Delta: CSP headers — V1

Status: **approved** (2026-11-25)

Parent: [SPEC.md](../SPEC.md) · Capability map: [capability-map.md](capability-map.md) · Risk source: [ADR 0003](../docs/adr/0003-auth-architecture.md) §Verification and residual risk, row "No CSP is configured yet"

## Objective

Close the XSS token-theft residual in ADR 0003 by emitting a strict `Content-Security-Policy` from Symfony for every response. Inline JavaScript is limited to the existing Twig globals script and is authorized by a fresh per-response nonce; no `'unsafe-eval'`, no `'unsafe-inline'` for scripts and no remote host allowlists are introduced. The JWT contract, localStorage token storage, login/refresh/logout behaviour and the rate limits shipped in `auth-rate-limiting` must not change.

## Assumptions to confirm

1. The approved capability-map policy is used verbatim (including `style-src 'self' 'unsafe-inline'` because PrimeVue and Sakai templates inject inline styles at runtime).
2. **Decided 2026-11-24:** remote media in two demo-only views — the `primefaces.org` product/avatar images (`assets/views/uikit/List.vue:112,147`; `assets/views/uikit/Table.vue:186,293`) and the YouTube embed (`assets/views/utilities/Documentation.vue:40`) — will be replaced with local assets in this module, because `img-src 'self' data:` and `default-src 'self'` will block them. No remote origin will be allowlisted to keep them.
3. WebProfilerBundle adding `'unsafe-inline'` to script/style directives in `dev` is an accepted development-only artifact; the enforceable policy is verified in `test` (toolbar off) and in a prod-like smoke.
4. The `new Function('return this')()` fallback at `public/build/runtime.js:125` is unreachable in supported browsers (`typeof globalThis === 'object'` returns first), so no `'unsafe-eval'` is needed.

→ Correct any of these before approving the spec.

## Tech stack

Symfony 7.4 / PHP 8.2, Twig 3, Webpack Encore + Vue 3.5 runtime-only build (`runtimeCompilerBuild: false`), PrimeVue 5 / Sakai 5. No new Composer or npm dependency, no new test framework. The nonce is generated with PHP's CSPRNG (`random_bytes`).

## Commands

```bash
# Rebuild assets if the demo media replacements are approved
npm run build

# Focused RED-first test, then the full gate
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit tests/Functional/CspHeadersTest.php
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit

# Manual smoke: fetch the SPA shell and inspect the header + inline script nonce
curl -sSI http://localhost:8180/ | grep -i '^content-security-policy:'
```

A browser smoke (DevTools console: zero CSP violations across login and several SPA routes) is required; no automated browser test framework is added.

## Project structure

| Path                                                                                                      | Change                                                                                                                                      |
|-----------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------|
| `src/EventSubscriber/CspResponseSubscriber.php`                                                           | New: generate nonce on `kernel.request`, emit the policy on `kernel.response`                                                               |
| `config/services.yaml`                                                                                    | Bind the policy string (or subscriber argument) if not kept as a class constant                                                             |
| `templates/base.html.twig`                                                                                | Add `nonce="{{ app.request.attributes.get('csp_nonce') }}"` to the globals `<script>`                                                       |
| `assets/views/uikit/List.vue`, `assets/views/uikit/Table.vue`, `assets/views/utilities/Documentation.vue` | Replace remote demo media with local assets (if assumption 2 is approved)                                                                   |
| `tests/Functional/CspHeadersTest.php`                                                                     | New RED-first functional coverage for directives, nonce presence/rotation and absence of unsafe tokens                                      |
| `spec/SPEC-csp-headers.md`                                                                                | This approved delta spec (committed after approval)                                                                                         |
| `tasks/plan-hardening-csp.md`, `tasks/todo-hardening-csp.md`                                              | Dedicated plan/task files — do not overwrite `tasks/plan-sakai-primevue-ts.md` / `tasks/todo-sakai-primevue-ts.md`, which keep Task 35 open |
| `docs/adr/0003-auth-architecture.md`, `README.md`                                                         | After verification: mark the CSP row mitigated and document the policy                                                                      |

No change to `public/index.php`, the Caddyfiles or Apache config: the header is emitted by Symfony so it follows Twig-rendered responses and can carry the per-request nonce.

## Code style

Follow the existing PHP style in `src/Security/LoginRateLimitFailureListener.php`: `#[AsEventListener]`-style subscriber registration via `getSubscribedEvents()`, explicit return types, constructor injection, no strict-types declaration (not used in this codebase today). Intended nonce flow:

```php
// kernel.request: fresh nonce per master request, stored on the Request
$request->attributes->set('csp_nonce', rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='));

// kernel.response: same value is interpolated into the header
$nonce = $event->getRequest()->attributes->get('csp_nonce');
$response->headers->set('Content-Security-Policy', sprintf(
    "default-src 'self'; script-src 'self' 'nonce-%s'; ...",
    $nonce,
));
```

Twig uses the same request attribute; no shared mutable service state (FrankenPHP worker-safe):

```twig
<script nonce="{{ app.request.attributes.get('csp_nonce') }}">
    window.PRIMEUI_LICENSE_KEY = '{{ primeui_license_key|e('js') }}';
</script>
```

### Sources for this pattern

- <https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy> — nonce source syntax, `'self'` fallback, multiple policies, `'unsafe-eval'`/`'unsafe-inline'` semantics
- <https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/script-src> — nonce must be freshly generated per response; `eval()`, `Function()` and string timers are blocked without `'unsafe-eval'`
- <https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP#nonces> — nonce delivery on `<script>` elements
- <https://symfony.com/doc/7.4/event_dispatcher.html> — event subscribers/listeners
- <https://symfony.com/doc/7.4/templates.html> — `app.request` in Twig
- Verified locally: `vendor/symfony/http-kernel/EventListener/ErrorListener.php:120-130` (CSP removed on debug error responses), `vendor/symfony/web-profiler-bundle/Csp/ContentSecurityPolicyHandler.php:114-154` (dev toolbar injects `'unsafe-inline'`), `node_modules/@symfony/webpack-encore/lib/config-generator.js:127-134` (runtime-only Vue build is CSP-compliant), `public/build/runtime.js:122-130` (global fallback short-circuits on `globalThis`)

## New behaviour

1. **Policy.** Exact enforced header (the `<nonce>` placeholder is the request's base64url nonce):

   ```http
   Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-<nonce>'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'
   ```

   No host/scheme allowlists, no `'unsafe-eval'`, no `'unsafe-inline'` in `script-src`, no `report-*` or `strict-dynamic` in V1. `frame-src` is intentionally omitted and falls back to `default-src 'self'`.
2. **Nonce lifecycle.** 18 random bytes → 24-character base64url string (144 bits entropy) generated on `kernel.request` for the master request and stored as the `csp_nonce` Request attribute. The same value is used by Twig and the header, so two concurrent requests can never share a nonce. No service holds request state between calls.
3. **Scripts.** The only inline script in `templates/base.html.twig:15-17` receives the nonce. Encore's `runtime.js`, `vendors-*.js`, `app.js` and dynamically injected chunk tags are external same-origin scripts covered by `'self'`; `__webpack_nonce__` is therefore unnecessary. Vue SFC templates are compiled at build time (`runtimeCompilerBuild: false`), and app source contains no `eval`, `new Function` or string-argument timers. The webpack `new Function` fallback is verified unreachable in supported browsers.
4. **Styles.** PrimeVue injects `<style>` elements and the app uses inline `style` attributes/` :style` bindings extensively; `style-src 'self' 'unsafe-inline'` keeps those working. This is the accepted residual for V1; narrowing it (hashes/nonce plus removing inline styles) is deferred. Passing the nonce to PrimeVue's `csp.nonce` option would not tighten the policy while `'unsafe-inline'` remains and is therefore deferred.
5. **Header scope.** The subscriber sets the header on every response, including JSON API responses; they contain no executable markup and are unaffected functionally. In `dev` only, WebDebugToolbarListener may append its own nonces and `'unsafe-inline'` to the header — this is a known local-only difference. In debug mode Symfony's ErrorListener removes CSP from exception responses at priority -128; error pages are not in the test matrix.
6. **Remote demo media.** If assumption 2 is approved, the four remote `<img>` sources and the YouTube `<iframe>` are swapped for existing local `/demo/images/...` assets or a local placeholder so the demo views render under `default-src 'self'`. External `<a href>` links are navigation, not subresources, and remain unchanged.
7. **Test isolation.** No cache clearing is needed; the nonce is per-request. The functional test asserts the header and the rendered HTML from the same response.

## Testing strategy

- RED first: add `tests/Functional/CspHeadersTest.php` before adding the subscriber or Twig nonce; observe failures.
- Small/medium functional tests via the existing `WebTestCase` + `KernelBrowser` harness (no JS engine, no new framework). Cases:
  - `GET /` returns 200, HTML contains the nonced globals script, and the header matches the rendered nonce.
  - Header contains every required directive with the exact approved values; `script-src` contains no `'unsafe-inline'`, `'unsafe-eval'`, `http:`/`https:` host or `*` source.
  - Two requests produce different nonces; nonce decodes to ≥16 bytes and uses only base64url characters.
  - A JSON auth endpoint response still returns its normal status/body with the header present.
- Full gate: `simple-phpunit` shows no regressions. Frontend gates (`npm test`, `npm run lint`, `npm run typecheck`) are unchanged unless demo assets are edited, in which case `npm run build` must also pass.
- Manual: browser smoke with zero console CSP violations on `/`, `/auth/login`, a route that triggers a dynamic chunk load, and the demo views touched by item 6. Confirm the enforced header in a prod-like run (dev toolbar off).

## Boundaries

- **Always:** generate a fresh CSPRNG nonce per request; keep the nonce and header from the same Request; run the focused and full PHPUnit gates plus a browser smoke before marking the risk mitigated; rebuild assets if demo media changes.
- **Ask first:** adding any remote origin or `data:`/`blob:` source beyond the approved list, adding `'unsafe-eval'` or `strict-dynamic`, narrowing/enabling inline styles beyond the accepted residual, changing the header scope, adding a reporting endpoint, or adding a browser test framework.
- **Never:** commit secrets or change the JWT/refresh/cookie contract, reuse a nonce across responses, weaken an existing auth or rate-limit check to satisfy a test, edit vendor files, or cut `v1.0.0` before the `hardening-release` module.

## Success criteria

- [ ] RED-first `CspHeadersTest.php` failed before the change and passes afterwards; full `simple-phpunit` suite is green.
- [ ] `GET /` emits the exact approved policy with a fresh base64url nonce that matches the inline `<script nonce="...">`.
- [ ] Two consecutive responses carry different nonces; `script-src` has neither `'unsafe-eval'` nor `'unsafe-inline'`; no remote host appears in any directive.
- [ ] SPA login, dynamic chunk loading and all demo views render with zero CSP violations in a browser smoke.
- [ ] ADR 0003 CSP row is marked mitigated and README documents the policy; no secret is present in the diff.
- [ ] Task 35 in the Sakai plan remains open and dated November 2027.

## Out of scope (deferred)

- Trusted Types / `require-trusted-types-for`, `strict-dynamic`, hash-based inline script allowlists, `report-uri`/`report-to` collection endpoints.
- Narrowing `style-src` by removing PrimeVue/inline styles; wiring PrimeVue `csp.nonce` while `'unsafe-inline'` remains.
- Other security headers (HSTS, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`) and Caddy-level header injection.
- Moving the access token out of `localStorage` (needs a new ADR), SPA deep-link rewrite, dev-toolchain `npm audit` triage.
- Browser test framework, Symfony Reprise/Vite (November 2027), and cutting the `v1.0.0` tag (`hardening-release` module).

## Open questions

Approved as written on 2026-11-25: both items below proceed with the recommended defaults, so neither blocks planning or implementation.

1. **Dev toolbar (assumption 3):** accept that `dev` shows `'unsafe-inline'` added by WebProfilerBundle, with the enforceable policy verified in `test`/prod-like mode? Recommended: accept.
2. **Header scope:** emit on all responses (as specified) or only on HTML responses? Emitting everywhere is simpler and harmless, but it adds a header to JSON API responses.
