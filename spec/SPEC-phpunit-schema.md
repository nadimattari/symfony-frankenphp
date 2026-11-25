# Spec: PHPUnit schema warning (`phpunit-schema`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Remove the `phpunit.xml.dist` configuration-validation warning while leaving the test suite, its settings and its assertions untouched. A reviewer should be able to run the unchanged suite and see no "configuration did not pass validation" message.

## Source verification

- Installed runner: `simple-phpunit` with PHPUnit 9.6.38 (`vendor/bin/.phpunit/phpunit-9.6-0/phpunit.xsd`).
- That schema defines `<coverage>` (with `include`, `exclude`, `report`) in `configGroup` and has **no** `<source>` element. `<source>` is PHPUnit 10+ syntax, which explains the current warning at line 18.
- The `xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"` attribute points to a path that simple-phpunit does not create; PHPUnit validates against its own bundled schema, so this attribute is not the cause of the warning. Do not hardcode the version-specific cache path unless verification proves the attribute must change; if it does, record the reason.

## Commands

```bash
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit --version
git diff --check
```

## Project structure

| Path | Change |
|---|---|
| `phpunit.xml.dist` | Replace the `<source><include><directory>src</directory></include></source>` block with the schema-valid 9.6 `<coverage><include><directory>src</directory></include></coverage>` block |
| `spec/SPEC-phpunit-schema.md`, `tasks/plan-phpunit-schema.md`, `tasks/todo-phpunit-schema.md` | Dedicated spec/plan/tasks for this module |

Everything else in `phpunit.xml.dist` stays byte-identical: bootstrap, colors, testsuites, and all `<php>` ini/server settings. No test file, source file, composer manifest or lock file changes.

## Acceptance criteria

- [ ] `simple-phpunit` no longer prints the configuration-validation warning or the "Element 'source': This element is not expected" message.
- [ ] Test count and assertion count remain `OK (58 tests, 375 assertions)`; no test, assertion or threshold is removed or relaxed.
- [ ] The suite's exit code and any remaining deprecation notices are recorded truthfully. The `eraseCredentials()` deprecations belong to the next module and may still be present here.
- [ ] One local commit containing only this module; no push and no tag.

## Out of scope

The `eraseCredentials()` deprecation, PHP version changes, PHPUnit 10 migration, coverage report generation, and any change to test behaviour or CI thresholds.
