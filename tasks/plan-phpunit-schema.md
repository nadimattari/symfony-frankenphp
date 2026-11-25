# Implementation Plan: PHPUnit schema warning (`phpunit-schema`)

Status: **approved** (2026-11-25)

Source spec: [SPEC-phpunit-schema.md](../spec/SPEC-phpunit-schema.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Replace the PHPUnit 10-style `<source>` element in `phpunit.xml.dist` with the `<coverage>` element defined by the installed PHPUnit 9.6 schema, then run the unchanged suite and record the exact output and exit code. This is a one-file config fix; the 17 `eraseCredentials()` deprecations are deliberately left for the next module.

## Architecture decisions

- Use the schema actually shipped with `simple-phpunit` 9.6.38 (`vendor/bin/.phpunit/phpunit-9.6-0/phpunit.xsd`), which defines `<coverage>` and has no `<source>` element. Do not migrate to PHPUnit 10 syntax or add a coverage report.
- Change only the four-line block at lines 18–22. Bootstrap, colors, testsuites and all `<php>` ini/server settings stay byte-identical.
- Leave `xsi:noNamespaceSchemaLocation` unchanged: PHPUnit validates against its own bundled schema, and pointing it at the version-specific simple-phpunit cache would be brittle. If verification contradicts this, record the reason in the task file before changing it.
- Success is defined by the absence of the validation warning and unchanged test/assertion counts — **not** by assuming exit 0, since deprecations may still be present until the next module.

## Task list

### Phase 1: Fix and verify

- [x] P1 — Replace `<source>` with schema-valid `<coverage>` in `phpunit.xml.dist`
- [x] P2 — Run the unchanged suite and record output, counts and exit code

### Checkpoint: Warning gone, suite unchanged

- [x] No configuration-validation warning; `OK (58 tests, 375 assertions)` unchanged
- [x] Any remaining deprecations are attributed only to `eraseCredentials()`

### Phase 2: Close the slice

- [x] P3 — Hygiene, single local commit, no push or tag

### Checkpoint: Complete

- [x] One commit containing only this module; working tree clean; no push, no tag

## Verification commands

```bash
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit --version
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
git diff --check
git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only
git status --short --branch
git tag --list
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Suite still exits 1 because of the 17 `eraseCredentials()` deprecations | Medium — mistaken claim of a green gate | Record the exit code and its causes; leave the deprecation fix to the next module |
| Coverage settings alter test behaviour | Medium — hidden scope creep | Keep the `include` directory identical (`src`) and change no other XML element |
| IDE-only schemaLocation path is stale | Low — cosmetic | Leave unchanged unless verification proves it relevant; document any deviation |

## Open questions

None. The module boundary and defaults were approved in the capability map and spec.
