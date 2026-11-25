# Implementation Plan: Remove Lamiral-specific roles (`remove-lamiral-roles`)

Status: **approved** (2026-11-25) — covered by the approved capability map and spec

Source spec: [SPEC-remove-lamiral-roles.md](../spec/SPEC-remove-lamiral-roles.md) · [capability map](../spec/capability-map-pre-push-cleanup.md)

## Overview

Reduce the role model to `ROLE_ADMIN` / `ROLE_USER`: delete the cashier/customer constants from the entity and store, 
delete the unused store getters and cashier branch, simplify the security hierarchy, replace the guard-test fixture, 
add a RED-first store test, and record a dated ADR note without rewriting history.

## Architecture decisions

- Test first: add an absence test to `tests/frontend/auth-store.test.cjs` and observe RED before touching production 
code.
- Keep `isAuthorisedFor()` semantics: admin authorises everything; everyone else matches membership exactly.
- Use a synthetic `"ROLE_FIXTURE"` literal for the guard test so no removed role name survives in live tests and no new
production role is invented.
- Document the simplification as a dated ADR subsection; preserve line 31 and all old task-log entries.
- No database migration: stale JSON role strings are inert after the hierarchy simplification and are called out in the 
ADR note.

## Task list

### Phase 1: RED test
- [ ] R1 — Add the store absence test and confirm it fails
  - Acceptance: test asserts the compiled store lacks `ROLE_CASHIER`, `ROLE_CUSTOMER`, `isStaff` and `isCustomer`; it 
  fails against the current code.
  - Verify: `docker compose exec -T sf-node npm run test:fetch` (RED).

### Checkpoint: Reproduction proven
- [ ] New test fails for the expected reason; no existing test was modified.

### Phase 2: GREEN slice
- [ ] R2 — Remove the role constants, getters, cashier branch, hierarchy entry and guard-test literal
  - Acceptance: `src/Entity/User.php`, `assets/store/auth.ts`, `config/packages/security.yaml` and 
  `tests/frontend/auth-guard.test.cjs` match the spec; admin-authorises-all still works.
  - Verify: `npm run test:fetch` GREEN, `git grep` clean over live code, `git diff --check`.

### Checkpoint: Behaviour green
- [ ] All frontend tests pass including the new absence test; no live role references remain.

### Phase 3: Document and close
- [ ] R3 — Add the dated ADR note, run all gates and commit locally
  - Acceptance: ADR original text untouched; dated note added; frontend gates exit 0; PHPUnit `OK (58 tests, 375 
  assertions)`; one commit; no push/tag.
  - Verify: `docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run 
  typecheck'`, PHPUnit, `git status --short --branch`, `git tag --list`.

### Checkpoint: Complete
- [ ] Clean tree, one local commit, no push, no tag.

## Verification commands

```bash
docker compose exec -T sf-node npm run test:fetch
docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
git grep -n -E 'ROLE_(CASHIER|CUSTOMER)|USER_ROLE_(CASHIER|CUSTOMER)'
git diff --check
git status --short --branch
git tag --list
```

## Risks and mitigations

| Risk                                    | Impact | Mitigation                                                                          |
|-----------------------------------------|--------|-------------------------------------------------------------------------------------|
| Removing getters used outside the store | Medium | Repo-wide grep showed no consumers; TypeScript gate will catch any missed typed use |
| Stored roles in the database            | Low    | No migration in this slice; inert strings documented in the ADR note                |
| Accidental history rewrite              | Medium | Append a dated subsection only; verify the ADR diff is additive                     |

## Open questions

None.
