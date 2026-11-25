# Tasks: PHPUnit schema warning (`phpunit-schema`)

Status: **approved** (2026-11-25) · [plan](plan-phpunit-schema.md)

Source spec: [SPEC-phpunit-schema.md](../spec/SPEC-phpunit-schema.md)

## P1 — Replace `<source>` with schema-valid `<coverage>`

**Description:** Edit only `phpunit.xml.dist`, changing the PHPUnit 10-style `<source>` block into the `<coverage><include>` form defined by the installed PHPUnit 9.6 schema.

**Acceptance criteria:**
- [x] The four-line `<source>` block is replaced by `<coverage><include><directory>src</directory></include></coverage>`.
- [x] Every other line of the file is unchanged.

**Verification:**
- [x] `git diff -- phpunit.xml.dist` shows only that block.
- [x] `git diff --check` passes.

**Dependencies:** None

**Files likely touched:** `phpunit.xml.dist`

**Estimated scope:** XS

## P2 — Run the unchanged suite and record the result

**Description:** Run `simple-phpunit` exactly as before and record the warning state, test/assertion counts, exit code and any remaining deprecations.

**Acceptance criteria:**
- [x] No `configuration file did not pass validation` warning and no `Element 'source': This element is not expected` message.
- [x] Output still reports `OK (58 tests, 375 assertions)`.
- [x] Any remaining deprecation notices are recorded with their exact cause; no attempt is made to hide them here.

**Verification:**
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit --version`
- [x] `docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit`

**Dependencies:** P1

**Files likely touched:** `tasks/todo-phpunit-schema.md` (recorded results only)

**Estimated scope:** XS

### Checkpoint: Warning gone, suite unchanged

- [x] Configuration validates; test and assertion counts unchanged
- [x] Remaining deprecations, if any, are attributed only to `eraseCredentials()`

## P3 — Hygiene and single local commit

**Description:** Stage only this module's files, review the diff and secret scan, and make one local commit. Do not push or tag.

**Acceptance criteria:**
- [x] Staged diff contains `phpunit.xml.dist` plus the dedicated spec/plan/tasks files only.
- [x] One local commit with a descriptive `fix:` message; no push and no new tag.
- [x] Working tree is clean afterwards.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`
- [x] `git diff --check`

**Dependencies:** P2

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] All spec acceptance criteria are met and results are recorded truthfully
- [x] One commit, clean tree, no push, no tag

## Recorded verification results

- Runner: `PHPUnit 9.6.38 by Sebastian Bergmann and contributors.` (`vendor/bin/simple-phpunit --version`).
- Full suite: `OK (58 tests, 375 assertions)`, 58/58 tests, no configuration-validation warning and no `Element 'source': This element is not expected` message. Process exit code was **1** solely because of 17 remaining indirect deprecation notices.
- Deprecations: all 17 are `Since symfony/security-http 7.3: Implementing "App\Entity\User::eraseCredentials()" is deprecated since Symfony 7.3...`; they are intentionally left for the `erase-credentials` module.
- `git diff --check` clean; staged secret scan showed no literal credential value; working tree clean after the module commit; tags remain `pre-migration`, `v0.1.0`, `v0.2.0` with no `v1.0.0` and no push.
