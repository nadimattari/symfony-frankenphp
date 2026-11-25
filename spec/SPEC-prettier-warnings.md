# Spec: Prettier warnings (`prettier-warnings`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Clear the 2586 Prettier warnings from `npm run lint` by formatting the existing lint scope, without changing any lint rule, threshold, dependency or test.

## Current behaviour

- `npm run lint` runs `eslint assets webpack.config.js eslint.config.mjs` and exits **0** with 2586 `prettier/prettier` warnings and 0 errors.
- `eslint.config.mjs` sets `prettier/prettier` to `"warn"` (via `eslint-plugin-prettier` recommended config). The warning level is intentional and must remain; disabling the rule to manufacture a clean result is explicitly out of scope.

## Approach

- Run the repository's own ESLint with `--fix` over the same scope (e.g. `npm run lint -- --fix`) so Prettier's formatting is applied in place.
- Review the resulting diff to confirm it is formatting only: no rule change in `eslint.config.mjs`, no change to `package.json`, `package-lock.json`, build config semantics or test files' logic.
- Record the before/after warning counts honestly.

## Commands

```bash
docker compose exec -T sf-node npm run lint
docker compose exec -T sf-node npm run lint -- --fix
docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'
git diff --stat
git diff --check
```

Note: `npm test` does not exist in this repository; the authoritative frontend gates are `build`, `test:fetch`, `lint` and `typecheck`.

## Project structure

| Path | Change |
|---|---|
| Files already in the lint scope under `assets/`, plus `webpack.config.js` and `eslint.config.mjs` if Prettier reports issues there | Formatting only, applied by ESLint `--fix` |
| `spec/SPEC-prettier-warnings.md`, `tasks/plan-prettier-warnings.md`, `tasks/todo-prettier-warnings.md` | Dedicated spec/plan/tasks for this module |

No rule severity, plugin, Prettier config, dependency, lock file, application logic or test change.

## Acceptance criteria

- [ ] `npm run lint` exits 0 with **0 warnings and 0 errors**.
- [ ] `npm run build`, `npm run test:fetch` and `npm run typecheck` still exit 0.
- [ ] `prettier/prettier` remains configured as a warning; no rule is disabled or downgraded.
- [ ] A diff review confirms formatting-only changes in the existing lint scope.
- [ ] One local commit containing only this module; no push and no tag.

## Out of scope

Changing ESLint/Prettier configuration or versions, adding a formatting CI gate, reformatting files outside the existing lint scope, and functional refactors of formatted files.
