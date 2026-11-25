# Task List: Remove Lamiral-specific roles (`remove-lamiral-roles`)

Companion to [plan-remove-lamiral-roles.md](plan-remove-lamiral-roles.md). Spec: [SPEC-remove-lamiral-roles.md](../spec/SPEC-remove-lamiral-roles.md).

## Task R1: RED — absence test for removed roles

**Description:** Add a test to the compiled-store suite that proves the two Lamiral role constants and the two
Lamiral-shaped getters are still present, so it fails before the production change.

**Acceptance criteria:**
- [ ] Test asserts `ROLE_CASHIER`, `ROLE_CUSTOMER`, `isStaff` and `isCustomer` are absent from the store module/instance.
- [ ] `npm run test:fetch` fails with this test before any source change.

**Verification:**
- [ ] RED output recorded in the results section.

**Dependencies:** None

**Files likely touched:**
- `tests/frontend/auth-store.test.cjs`

**Estimated scope:** XS (1 file)

## Task R2: GREEN — remove roles from live code and config

**Description:** Delete the constants and getters, simplify the hierarchy and `isAuthorisedFor()`, and replace the 
guard-test fixture with a synthetic role.

**Acceptance criteria:**
- [ ] `User.php`, `auth.ts`, `security.yaml` and `auth-guard.test.cjs` match the spec table.
- [ ] Admin-authorises-all and plain membership behaviour remain; all frontend tests pass.
- [ ] `git grep` finds no removed role names in `src/`, `assets/`, `config/` or `tests/`.

**Verification:**
- [ ] `docker compose exec -T sf-node npm run test:fetch` exits 0.
- [ ] `git diff --check` clean.

**Dependencies:** R1

**Files likely touched:**
- `src/Entity/User.php`, `assets/store/auth.ts`, `config/packages/security.yaml`, `tests/frontend/auth-guard.test.cjs`

**Estimated scope:** M (4 files)

## Task R3: Document, verify and commit

**Description:** Append the dated ADR note, run the full frontend and PHPUnit gates, and make one local commit.

**Acceptance criteria:**
- [ ] ADR diff is additive only; line 31 and old task logs unchanged.
- [ ] Frontend gates exit 0; PHPUnit `OK (58 tests, 375 assertions)`, exit 0.
- [ ] One local commit; no push, no tag.

**Verification:**
- [ ] All commands in the plan's verification section.

**Dependencies:** R2

**Files likely touched:**
- `docs/adr/0003-auth-architecture.md`

**Estimated scope:** S (1 file + verification)

## Recorded results

Pending implementation.
