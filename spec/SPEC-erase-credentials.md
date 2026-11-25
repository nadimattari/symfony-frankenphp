# Spec: `eraseCredentials()` deprecation (`erase-credentials`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Silence the Symfony 7.3+ deprecation for `App\Entity\User::eraseCredentials()` without changing the authentication contract. The current full suite reports 17 indirect deprecations, all with the same cause.

## Source verification

- `vendor/symfony/security-core/User/UserInterface.php` still declares `public function eraseCredentials(): void;` and marks it `@deprecated since Symfony 7.3, erase credentials using the "__serialize()" method instead`.
- Symfony's own implementations (`InMemoryUser`, `OidcUser`) keep the method and add `#[\Deprecated(since: 'symfony/security-core 7.3')]`; the deprecation notice explicitly asks userland implementations to add the same attribute.
- Therefore the method **cannot simply be deleted**: the interface still requires it. The minimal correct fix is to add the attribute to the existing empty method.

## Commands

```bash
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
git diff --check
```

## Project structure

| Path | Change |
|---|---|
| `src/Entity/User.php` | Add `#[\Deprecated(since: 'symfony/security-core 7.3')]` immediately above the existing empty `eraseCredentials(): void` method; keep the method body and signature unchanged |
| `spec/SPEC-erase-credentials.md`, `tasks/plan-erase-credentials.md`, `tasks/todo-erase-credentials.md` | Dedicated spec/plan/tasks for this module |

No firewall, token, refresh-cookie, session or serializer change. Do not introduce `__serialize()` in this module — that would alter object persistence/session behaviour and is not needed to clear the notice.

## Acceptance criteria

- [ ] Full PHPUnit output contains **zero** `eraseCredentials()` deprecation notices.
- [ ] Test and assertion counts remain `OK (58 tests, 375 assertions)`.
- [ ] Login, refresh, logout and 429/Retry-After functional coverage still pass unchanged.
- [ ] The suite exit code is recorded truthfully; with the schema module already landed, no known deprecation or schema cause should remain.
- [ ] One local commit containing only this module; no push and no tag.

## Out of scope

Removing the interface method, adding `__serialize()`, changing JWT/refresh/session handling, and touching the shipped auth rate-limiting spec or tests.
