# Spec Delta: Remove Lamiral-specific roles (`remove-lamiral-roles`)

Status: **proposed** (2026-11-25)

Source: [capability map](capability-map-pre-push-cleanup.md) · user decision: `ROLE_CASHIER` and `ROLE_CUSTOMER` are Lamiral-domain roles and are not 
needed in this starter.

## Objective

Reduce the shipped role model to `ROLE_ADMIN` and `ROLE_USER` by removing the Lamiral-specific cashier/customer 
constants, UI getters, hierarchy entry and test literal. Keep the existing admin-authorises-all behaviour and the 
JWT/auth contract unchanged.

## Current behavior

- `src/Entity/User.php` declares `USER_ROLE_CASHIER` and `USER_ROLE_CUSTOMER` alongside `USER_ROLE_USER` and 
`USER_ROLE_ADMIN`.
- `assets/store/auth.ts` exports `ROLE_CASHIER` and `ROLE_CUSTOMER`; `isStaff` checks admin-or-cashier; `isCustomer` 
checks customer while excluding admin/cashier; `isAuthorisedFor()` has a cashier special case.
- `config/packages/security.yaml` maps `ROLE_ADMIN: [ROLE_CASHIER]` and `ROLE_CASHIER: [ROLE_USER]`.
- `tests/frontend/auth-guard.test.cjs` uses `"ROLE_CASHIER"` as an arbitrary role fixture in the admin-authorises-all 
test.
- `docs/adr/0003-auth-architecture.md` line 31 records the original four-role decision. Task logs from 2026-11-24 also 
mention it.

## New behavior

| Path                                 | Change                                                                                                                                                                                                    |
|--------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `src/Entity/User.php`                | Delete `USER_ROLE_CASHIER` and `USER_ROLE_CUSTOMER`; keep the two remaining constants and all methods unchanged                                                                                           |
| `assets/store/auth.ts`               | Delete `ROLE_CASHIER` and `ROLE_CUSTOMER` exports; delete the unused `isStaff` and `isCustomer` getters; delete the cashier branch in `isAuthorisedFor()`; keep admin-authorises-all and plain membership |
| `config/packages/security.yaml`      | Replace the two hierarchy lines with `ROLE_ADMIN: [ROLE_USER]`                                                                                                                                            |
| `tests/frontend/auth-guard.test.cjs` | Replace the `"ROLE_CASHIER"` fixture with a synthetic `"ROLE_FIXTURE"` string; no production role is introduced                                                                                           |
| `tests/frontend/auth-store.test.cjs` | Add a RED-first test asserting the compiled store no longer exports the two removed constants and has no `isStaff` / `isCustomer` getters                                                                 |
| `docs/adr/0003-auth-architecture.md` | Add a short dated subsection noting the simplification; do not edit the original decision text or risk register                                                                                           |

Resulting store logic:

```ts
export const ROLE_ADMIN: Role = "ROLE_ADMIN";
export const ROLE_USER: Role = "ROLE_USER";

isAuthorisedFor(role: Role): boolean {
  if (this.roles.includes(ROLE_ADMIN)) {
    return true;
  }
  return this.roles.includes(role);
}
```

No migration is added. Existing database rows may still contain the old strings in their JSON `roles` column, but no 
code or hierarchy gives them meaning; this is noted in the ADR subsection.

## Commands

```bash
docker compose exec -T sf-node npm run test:fetch
docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
git grep -n -E 'ROLE_(CASHIER|CUSTOMER)|USER_ROLE_(CASHIER|CUSTOMER)'
git diff --check
```

## Project structure

| Path                                                                                                            | Change                       |
|-----------------------------------------------------------------------------------------------------------------|------------------------------|
| `src/`, `assets/store/`, `config/packages/`, `tests/frontend/`, `docs/adr/`                                     | Files listed above           |
| `spec/SPEC-remove-lamiral-roles.md`, `tasks/plan-remove-lamiral-roles.md`, `tasks/todo-remove-lamiral-roles.md` | Dedicated planning artifacts |

## Code style

Keep the existing double-quoted TypeScript style and four-space PHP/JSON/YAML indentation. The new ADR subsection 
follows the dated-note pattern already used for the CSP and proxy notes, e.g. `### Role model simplification 
(2026-11-25)`.

## Testing strategy

- **RED:** extend `tests/frontend/auth-store.test.cjs` with a test that requires the compiled store module and asserts 
`ROLE_CASHIER`, `ROLE_CUSTOMER`, `isStaff` and `isCustomer` are absent. Confirm it fails against the current code.
- **GREEN:** make the code/config/test-literal changes; rerun `npm run test:fetch` and confirm the new and existing 
tests pass.
- **Full gate:** `lint`, `build`, `test:fetch`, `typecheck` in `sf-node`, plus the unchanged PHPUnit suite. No test is 
skipped, weakened or deleted.

## Boundaries

- **Always:** keep `ROLE_ADMIN` / `ROLE_USER`, admin-authorises-all, the JWT/cookie/CSP contract and all existing test 
counts.
- **Ask first:** any data migration for stored role strings, access-control changes beyond the hierarchy line, or edits 
to shipped release notes.
- **Never:** rewrite the ADR's original decision text, edit 2026-11-24 task logs, or introduce replacement roles.

## Acceptance criteria

- [ ] The new store test was observed RED before the code change and GREEN after it; existing frontend tests are 
unchanged except for the fixture literal.
- [ ] `git grep` finds no `ROLE_CASHIER` / `ROLE_CUSTOMER` / `USER_ROLE_*` in `src/`, `assets/`, `config/` or `tests/`; 
remaining mentions are only the historical task log and the new dated ADR note.
- [ ] Frontend gates exit 0; PHPUnit reports `OK (58 tests, 375 assertions)` with exit 0.
- [ ] `docs/adr/0003-auth-architecture.md` keeps its original line 31 and gains only a dated note.
- [ ] One local commit; no push and no tag.

## Out of scope

- Task 35, release notes, `v1.0.0`, database migrations, new roles, and changes to Lamiral itself.
- Renaming `ROLE_ADMIN` / `ROLE_USER`, changing access-control rules, or touching JWT/refresh/CSP/rate-limit behaviour.

## Open questions

None.
