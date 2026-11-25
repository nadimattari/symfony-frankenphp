# Tasks: Prettier warnings (`prettier-warnings`)

Status: **approved** (2026-11-25) · [plan](plan-prettier-warnings.md)

Source spec: [SPEC-prettier-warnings.md](../spec/SPEC-prettier-warnings.md)

## W1 — Apply repository formatting

**Description:** Run `npm run lint -- --fix` in `sf-node` over the same scope as the normal lint command and record the before/after warning counts.

**Acceptance criteria:**
- [x] Before: 2586 `prettier/prettier` warnings, 0 errors.
- [x] After: 0 warnings, 0 errors, exit 0.

**Verification:**
- [x] `docker compose exec -T sf-node npm run lint -- --fix`

**Dependencies:** None

**Files likely touched:** Files under `assets/`, plus `webpack.config.js` or `eslint.config.mjs` only if Prettier reports issues there

**Estimated scope:** S

## W2 — Review the diff

**Description:** Confirm that only existing lint-scope files changed and that changes are formatting only; verify no rule, dependency, lock or test-logic edits.

**Acceptance criteria:**
- [x] `prettier/prettier` is still `"warn"`.
- [x] `package.json`, `package-lock.json`, `tests/` and rule configuration are unchanged.
- [x] No whitespace errors from `git diff --check`.

**Verification:**
- [x] `git status --short`, `git diff --stat`, `git diff -- eslint.config.mjs package.json package-lock.json tests`

**Dependencies:** W1

**Files likely touched:** `tasks/todo-prettier-warnings.md` (recorded results only)

**Estimated scope:** S

### Checkpoint: Clean lint

- [x] Lint clean without changing the rule severity

## W3 — Run all frontend gates

**Description:** Run `lint`, `build`, `test:fetch` and `typecheck` in `sf-node`.

**Acceptance criteria:**
- [x] All four commands exit 0.

**Verification:**
- [x] `docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'`

**Dependencies:** W2

**Files likely touched:** `tasks/todo-prettier-warnings.md` (recorded results only)

**Estimated scope:** S

## W4 — Hygiene and one local commit

**Description:** Stage only this module's files and make one local commit. No push or tag.

**Acceptance criteria:**
- [x] Staged diff contains only lint-scope formatting plus this module's plan/tasks.
- [x] One local commit; clean tree; no push, no new tag.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`

**Dependencies:** W3

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; clean tree; no push, no tag

## Recorded verification results

- Baseline lint in `sf-node`: exit 0 with `✖ 2586 problems (0 errors, 2586 warnings)`, all `prettier/prettier` and all fixable.
- `npm run lint -- --fix` in `sf-node`: exit 0. Post-fix `npm run lint`: exit 0 with no problem summary, i.e. **0 warnings and 0 errors**.
- Diff review: 57 files changed (7256 insertions, 2627 deletions), every path under the existing lint scope (`assets/`, `webpack.config.js`, `eslint.config.mjs`). No `package.json`, `package-lock.json`, `.prettier*` or `tests/` file changed.
- `eslint.config.mjs` was reformatted by Prettier but `"prettier/prettier": "warn"` remains at line 76; no rule was disabled, downgraded or removed.
- Frontend gates after formatting in `sf-node`: `lint`, `build`, `test:fetch`, `typecheck` all exit 0.
- `git diff --check` clean; staged secret scan showed no literal credential value; one local commit; no push and no new tag.
