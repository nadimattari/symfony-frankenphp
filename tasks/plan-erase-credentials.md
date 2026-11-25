# Implementation Plan: `eraseCredentials()` deprecation (`erase-credentials`)

Status: **approved** (2026-11-25) — covered by the approved capability map, spec and up-front plan-gate decision

Source spec: [SPEC-erase-credentials.md](../spec/SPEC-erase-credentials.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Add Symfony's own `#[\Deprecated]` attribute to the existing empty `User::eraseCredentials()` method so the 17 indirect PHPUnit deprecation notices disappear. The interface still declares the method, so it must remain; no auth behaviour changes.

## Architecture decisions

- Mirror Symfony's `InMemoryUser`/`OidcUser`: keep the method and add `#[\Deprecated(since: 'symfony/security-core 7.3')]`.
- Do not add `__serialize()` or touch firewall/token/session code — that would change behaviour beyond the deprecation fix.
- Success is measured by zero `eraseCredentials()` deprecation notices with test and assertion counts unchanged.

## Task list

### Phase 1: Fix and verify

- [ ] E1 — Add the deprecation attribute to `src/Entity/User.php`
- [ ] E2 — Run the unchanged suite and record deprecation count, counts and exit code

### Checkpoint: Deprecation gone

- [ ] Zero `eraseCredentials()` deprecations; `OK (58 tests, 375 assertions)`; auth coverage still passes

### Phase 2: Close the slice

- [ ] E3 — Hygiene and one local commit, no push or tag

### Checkpoint: Complete

- [ ] One commit for this module; clean tree; no push, no tag

## Verification commands

```bash
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
git diff --check
git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only
git status --short --branch
git tag --list
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Interface still requires the method | High if removed | Keep the signature and empty body; only add the attribute |
| Attribute not recognised by the installed Symfony version | Medium | Source-verified against `InMemoryUser`/`OidcUser`; verify with the full suite before committing |

## Open questions

None.
