# Tasks: `eraseCredentials()` deprecation (`erase-credentials`)

Status: **approved** (2026-11-25) · [plan](plan-erase-credentials.md)

Source spec: [SPEC-erase-credentials.md](../spec/SPEC-erase-credentials.md)

## E1 — Add the deprecation attribute

**Description:** Add `#[\Deprecated(since: 'symfony/security-core 7.3')]` immediately above the empty `eraseCredentials(): void` method in `src/Entity/User.php`, matching Symfony's own implementations.

**Acceptance criteria:**
- [x] Attribute present with the exact `since` value used by Symfony 7.3.
- [x] Method signature and body unchanged.

**Verification:**
- [x] `git diff -- src/Entity/User.php` shows only the added attribute line.
- [x] `git diff --check` passes.

**Dependencies:** None

**Files likely touched:** `src/Entity/User.php`

**Estimated scope:** XS

## E2 — Run the unchanged suite and record the result

**Description:** Run the full PHPUnit suite and confirm the 17 deprecation notices are gone.

**Acceptance criteria:**
- [x] Zero `eraseCredentials()` deprecation notices.
- [x] `OK (58 tests, 375 assertions)` with no schema warning.
- [x] Exit code and any remaining output recorded truthfully.

**Verification:**
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit`

**Dependencies:** E1

**Files likely touched:** `tasks/todo-erase-credentials.md` (recorded results only)

**Estimated scope:** XS

### Checkpoint: Deprecation gone

- [x] Zero notices for this method; tests and assertions unchanged

## E3 — Hygiene and one local commit

**Description:** Stage only this module's files, review the secret scan, and make one local commit. No push or tag.

**Acceptance criteria:**
- [x] Staged diff contains only `src/Entity/User.php` plus this module's plan/tasks.
- [x] One local commit with a descriptive `fix:` message; no push, no new tag.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`

**Dependencies:** E2

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] Spec acceptance criteria met; one commit; clean tree; no push, no tag

## Recorded verification results

- Full suite: `OK (58 tests, 375 assertions)`, 58/58 tests, no configuration-validation warning.
- `eraseCredentials()` deprecation notices: **0** (previously 17). The attribute was accepted by the installed Symfony version; auth tests and 429 coverage still pass.
- Process exit code was **1** from one unrelated, pre-existing indirect notice: `Since symfony/framework-bundle 7.3: Not setting the "framework.profiler.collect_serializer_data" config option to "true" is deprecated.` (`AppControllerTest::testIndex`). This is outside this module's scope and is recorded rather than silently fixed.
- `git diff --check` clean; staged secret scan showed no literal credential value; no push and no new tag.
