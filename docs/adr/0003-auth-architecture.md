# ADR 0003: JWT authentication with rotating refresh cookies

## Status

Accepted (2026-11-24)

## Context

The Sakai/PrimeVue SPA needs login, logout and silent token refresh. The starter currently has no authentication: `config/packages/security.yaml` uses an empty in-memory provider and the main firewall has no authenticators. The frontend already has the pieces that will consume the API: `assets/utils/fetch.ts` injects token storage and auth endpoints and performs single-flight refresh; `assets/store/auth.ts` keeps the access token, roles and user in `localStorage`; `assets/service/api.ts` defaults to the local `/api` base URL.

ADR 0002 decided to reuse Lamiral's auth backend as a read-only reference and to accept the `localStorage` access-token / httpOnly refresh-cookie trade-off. It did not record the token lifecycle, data model or endpoint contract. This ADR records those decisions before Tasks 25–28 implement them.

Constraints:

- Symfony 7.4 LTS, PHP 8.2, MariaDB 11.3.2; no change to the agreed platform.
- `lexik/jwt-authentication-bundle` 3.2.0 declares PHP `>=8.2` and Symfony `^6.4|^7.0|^8.0`, so it fits without a platform upgrade.
- API Platform is installed with `stateless: true`; its docs must not be publicly exposed outside development.
- No secrets, private keys or license keys may be committed.

## Decision

### Tokens and storage

1. Use **Lexik JWT** with RS256 key pairs for short-lived **access tokens**. The JWT travels in `Authorization: Bearer <token>` on API requests; the SPA stores it in `localStorage` under the `token` key (see `assets/store/auth.ts`). Default TTL is **3600 seconds**, configurable through `ACCESS_TOKEN_TTL`.
2. Use an opaque **refresh token** (`bin2hex(random_bytes(32))`) that is **never readable by JavaScript**: it is sent only in an `httpOnly`, `Secure`, `SameSite=Strict` cookie named `refresh_token` with path `/api`. The SPA cannot and must not persist it. Refresh token lifetime is **30 days**.
3. Store only `hash('sha256', $rawToken)` in the database. A leaked database row cannot be replayed as a cookie.
4. Use a separate double-submit **CSRF cookie** named `csrf` (path `/`, `httpOnly=false`, `Secure` in production, `SameSite=Strict`) so the SPA can read it and echo it in the `X-CSRF-Token` header. It is random per session and rotated together with the refresh token.

### Data model and migrations

5. Add a Doctrine `User` entity: unique `username`, hashed `password`, `roles` (JSON array), `name`, `active`, and a repository. Roles follow Lamiral's model (`ROLE_USER`, `ROLE_CASHIER`, `ROLE_ADMIN`, `ROLE_CUSTOMER`) with a hierarchy `ROLE_ADMIN` → `ROLE_CASHIER` → `ROLE_USER`.
6. Add a `RefreshToken` entity/table with: id, non-nullable many-to-one `user` (`onDelete: CASCADE`), `tokenHash` (64 chars, unique index), `expiresAt`, `createdAt`, nullable `revokedAt`. Generate and apply a Doctrine migration; verify with `doctrine:schema:validate`.
7. Add a `RefreshTokenService` that issues, rotates and revokes tokens. Rotation finds the active hash, revokes it and persists a replacement in one flush; a revoked, expired or disabled-user token is rejected. Logout revokes the presented token (best effort) and clears cookies.

### Endpoints and security

8. `POST /api/login`: public `json_login` with `username`/`password`. A success handler returns `{ token, expiresIn, data: { name, roles } }`, issues the refresh token, and sets both cookies. A user checker rejects disabled accounts. Bad credentials return a JSON 401.
9. `POST /api/token/refresh`: public, requires the `refresh_token` cookie **and** a matching `X-CSRF-Token` header. Rotates the token, returns a new JWT and user data, and sets the rotated cookies. Missing/invalid refresh cookie → 401 (cookies cleared); missing/mismatched CSRF → 403.
10. `POST /api/logout`: public, revokes the refresh cookie (validating CSRF when a cookie is present), clears both cookies and returns 204. The SPA clears its local state immediately without waiting for the request.
11. Main firewall is `stateless: true` with an entity user provider (by `username`), the `UserChecker`, `json_login` and `jwt` authenticators. Access control: the three auth endpoints and `/api/demo/*` are `PUBLIC_ACCESS`; other `/api` routes require `ROLE_USER`; API Platform docs are public only in dev, and are not registered at all outside dev. All other paths are public for now (SPA shell).
12. JWT key pairs and passphrase are environment-specific: `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY` and `JWT_PASSPHRASE` come from gitignored `.env.local` (or real environment variables) and keys live in gitignored `config/jwt/`. Never copy the reference project's passphrase or keys.

## Rationale

- A short-lived bearer token limits the value of an `XSS` theft; rotation plus revocation limits the value of a stolen refresh cookie.
- Keeping the refresh token in an httpOnly cookie prevents the SPA's JavaScript from reading or exfiltrating it, while the bearer header keeps API calls straightforward for non-browser clients and API Platform.
- Double-submit CSRF is required because the refresh/logout endpoints authenticate by cookie. The same header is also sent on bearer-authenticated mutations by the fetch wrapper, which is harmless and keeps one rule for all writes.
- Hashing refresh tokens at rest follows the reference implementation and avoids storing bearable credentials in the database.
- Lamiral's flow is a structural reference only. Its domain-specific layers (portal, reports, invoices, mail) are not ported.

## Alternatives considered

### Both tokens in `localStorage`

- **Pros:** simplest implementation; no cookie or CSRF handling.
- **Rejected:** any XSS could steal a token valid for 30 days and maintain indefinite access. The refresh token would be permanently exposed to frontend code.

### Both tokens in httpOnly cookies

- **Pros:** strongest protection against XSS token theft; browser handles transport.
- **Rejected for now:** requires CSRF protection on every API call and a different frontend contract (no readable token for the existing fetch wrapper or API Platform's `Authorization` header flow). Revisit if strict CSP proves insufficient.

### Stateful Symfony sessions instead of JWT

- **Pros:** simpler revocation, native CSRF integration, no key management.
- **Rejected:** conflicts with the stateless API Platform setup and the already-implemented bearer-token fetch wrapper; scales poorly across stateless workers.

### External identity provider (OIDC/OAuth2)

- **Pros:** mature login flows and MFA.
- **Rejected:** out of scope for this starter; would add an external dependency and new operational setup.

## Consequences

- **Accepted XSS risk:** the access token is readable by any script running in the SPA. Mitigations to keep: strict CSP with no inline scripts beyond the Twig-provided globals, no `eval`, vetted dependencies, short `ACCESS_TOKEN_TTL`, and no secrets in frontend code. The existing `npm audit` findings must be triaged before production. Moving the access token into an httpOnly cookie would require a new ADR and CSRF redesign.
- Silent refresh must be single-flight (already implemented in `FetchClient.refresh()`) so concurrent 401s do not spend the same rotating token twice.
- Login, refresh, logout and disabled-user paths need functional tests; a browser smoke must verify the cookie is httpOnly and that 401 retry works.
- Key generation, `.env.local` setup and gitignore rules are part of Task 26; forgetting them must fail the "no secrets in git" check in Task 28.
- `/api/demo/*` stays public so the demo fixtures keep working without authentication.

## Verification and residual risk (2026-11-24)

Tasks 26–27 are implemented and the flow was verified at three levels:

- **Backend functional tests:** `OK (22 tests, 88 assertions)`. They cover login (JWT shape, cookie attributes, disabled user, bad credentials), refresh rotation and replay rejection, CSRF rejection, unknown refresh token, logout revocation and cookie clearing, bearer auth on `/api/me`, and API Platform docs returning 404 outside dev.
- **Real HTTP flow against the dev stack:** login → 3-segment RS256 JWT plus `refresh_token` with `httponly; samesite=strict; path=/api` and a readable `csrf` cookie; `/api/me` with the bearer token → user JSON; refresh → rotated cookies and a new JWT; logout → 204; refresh after logout → 401. `/api/docs` returns 200 in dev and has no route in test.
- **Browser smoke at `http://localhost:8180/`:** logged-out navigation to `/pages/crud` redirects to `/auth/login?redirect=/pages/crud`; empty submit and bad credentials show their respective errors without storing a token; login stores `token`/`user`/`roles` and returns to `/pages/crud` with the demo table rendered; refresh using the readable CSRF cookie and `X-CSRF-Token` header succeeds in the browser without touching localStorage; the topbar Logout call returns 204, clears storage and cookies and returns to the login page; refresh afterwards returns 401. No console errors after fixing the topbar logo's relative URL.
- **Secret checks:** `git check-ignore` confirms `config/jwt/`, `.env.local` and `.env.test.local` are ignored; `git log --all -S'JWT_PASSPHRASE='` finds no committed passphrase; no `JWT_PASSPHRASE` or `PRIMEUI_LICENSE_KEY` value appears in tracked files. The dev smoke user is local database state, not committed seed data.
- **Auth rate limiting (2026-11-24):** `OK (55 tests, 358 assertions)` including five new RED-first functional tests for the 429 contract. Real HTTP smoke against the dev stack: the 11th bad login, 31st refresh and 31st logout returned 429 JSON with `Retry-After`, and a valid login after clearing the pool returned 200. Counters are stored in the MariaDB-backed `cache.rate_limiter` pool.

Risk register at sign-off — these are known gaps, not blockers for this starter:

| Risk                                         | Status    | Mitigation / follow-up                                                                                                                                                                                                           |
|----------------------------------------------|-----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Access-token theft via XSS                   | Accepted  | Token lives in `localStorage`; short 3600s TTL, no inline scripts beyond Twig globals, no `eval`, vetted dependencies. **No CSP is configured yet** — adding one is a prerequisite before exposing the SPA to untrusted content. |
| No rate limiting on `/api/login` or refresh  | Mitigated | Symfony login throttling allows 10 failed attempts/15 min per IP + username, with an IP-wide counter at 5× that limit that survives one user's success. Refresh/logout allow 30/15 min per hashed IP. Counters use the DBAL-backed `cache.rate_limiter` pool; limits return JSON 429 + `Retry-After` (login is minute-granular). Application rate limiting is not DoS protection; proxy-level controls remain out of scope. |
| Stolen access token stays valid until expiry | Accepted  | The stateless JWT cannot be revoked individually; logout revokes only the refresh token. Keep TTL short and move to httpOnly cookies (new ADR) if immediate revocation is required.                                              |
| Dev-only API Platform routes                 | Mitigated | All API Platform routes are registered under `when@dev` and `enable_docs: false` is the base config; `/api/demo/*` remains intentionally public for fixtures.                                                                    |
| SPA deep links                               | Open      | The dev server serves only `/`, so `/auth/login` and `/pages/*` load through client-side navigation. A real deployment needs a host rewrite to `index.php`, otherwise direct links 404.                                          |
| Dependency advisories                        | Open      | `npm audit --omit=dev` reports 0 vulnerabilities. The full dev/build toolchain reports 44 (3 critical, 20 high, 16 moderate, 5 low); triage by reachability before production, without forced upgrades.                          |
| Test-environment secrets                     | Mitigated | PHPUnit boots the `.env` chain and reads the passphrase from gitignored `.env.test.local`; CI must provision that file (or an equivalent environment variable) before running tests.                                             |

## Revisit

Re-evaluate the `localStorage` trade-off when any of the following changes:

- the SPA gains user-generated HTML/Markdown rendering or third-party scripts,
- CSP cannot be enforced or is routinely relaxed,
- a production incident involves token theft,
- the project adopts an external identity provider.
