# Implementation Plan: Prettier warnings (`prettier-warnings`)

Status: **approved** (2026-11-25) — covered by the approved capability map, spec and up-front plan-gate decision

Source spec: [SPEC-prettier-warnings.md](../spec/SPEC-prettier-warnings.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Run the repository's own ESLint with `--fix` over the existing lint scope to apply Prettier formatting where 2586 warnings were reported. Keep `prettier/prettier` at `"warn"` and verify all four frontend gates afterwards.

## Architecture decisions

- Use `docker compose exec -T sf-node npm run lint -- --fix`; do not install tools on the host or change scripts.
- Lint scope stays `assets webpack.config.js eslint.config.mjs`; no `package.json`, lock file, Prettier config, test logic or file outside that scope is edited manually.
- `prettier/prettier` remains `"warn"` in `eslint.config.mjs`; the fix is formatting output, not a rule change.
- Review the resulting diff by path and content, then run `lint`, `build`, `test:fetch` and `typecheck` in `sf-node`.

## Task list

### Phase 1: Apply formatting

- [x] W1 — Record the baseline warning count and run ESLint with `--fix`
- [x] W2 — Review the diff to confirm formatting-only changes in the existing lint scope

### Checkpoint: Clean lint

- [x] `npm run lint` exits 0 with 0 warnings and 0 errors; rule severity unchanged

### Phase 2: Verify and close

- [x] W3 — Run `build`, `test:fetch`, `lint` and `typecheck`
- [x] W4 — Hygiene and one local commit, no push or tag

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; no push, no tag

## Verification commands

```bash
docker compose exec -T sf-node npm run lint
docker compose exec -T sf-node npm run lint -- --fix
docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'
git diff --stat
git diff --check
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| `--fix` changes semantics | Medium | Review diff paths and gate results; no manual logic edits |
| Rule severity is weakened to hide warnings | High | Leave `eslint.config.mjs` rule as `"warn"` and verify the staged diff |
| Lock/manifest churn | Medium | Do not run `npm install`; verify `package.json` and lock file unchanged |

## Open questions

None.
