# Implementation Plan: Makefile frontend targets (`makefile-frontend-targets`)

Status: **approved** (2026-11-25) — covered by the approved capability map and spec

Source spec: [SPEC-makefile-frontend-targets.md](../spec/SPEC-makefile-frontend-targets.md) · [capability map](../spec/capability-map-pre-push-cleanup.md)

## Overview

Replace the Makefile's `sakai: TODO` placeholder with the useful `sf-node` targets from Lamiral's Makefile, fix the 
`NPM` variable so it no longer points at `sf-php`, and make node exec non-interactive with `-T`. No application 
behaviour or dependency changes.

## Architecture decisions

- Keep all target bodies one-line and silent, using the existing `$(NODE_CONT)` / `$(NPM)` variables and `##` help 
comments.
- Name the frontend test target `npm-test` (recipe `npm run test:fetch`) so Make does not parse `test:fetch:` as 
prerequisites.
- Use `docker compose exec -T sf-node` for recipes: safe in scripts and consistent with the documented verification 
commands.
- Dry-run install/watch/server targets only; execute the four fast gates through their new targets.

## Task list

### Phase 1: Makefile slice
- [ ] M1 — Update variables, `.PHONY`, replace the `sakai` placeholder with the nine frontend targets
  - Acceptance: `make help` lists all nine targets with descriptions; `NPM = $(NODE_CONT) npm`; `NODE_CONT` uses `-T`; 
  `sakai` is gone.
  - Verify: `make help`, `make -n npm-dev npm-watch npm-dev-server npm-install npm-ci npm-build npm-typecheck npm-lint 
  npm-test`, `git diff --check`.

### Checkpoint: Targets wired
- [ ] Help output and dry runs match the spec table exactly.

### Phase 2: Gate and close
- [ ] M2 — Run the four fast gates through the new targets; hygiene and one local commit
  - Acceptance: `make npm-lint npm-build npm-test npm-typecheck` all exit 0; diff limited to `Makefile` plus planning 
  artifacts; one commit; no push/tag.
  - Verify: full target run, `git status --short --branch`, `git tag --list`.

### Checkpoint: Complete
- [ ] Clean tree, one local commit, no push, no tag.

## Verification commands

```bash
make help
make -n npm-dev npm-watch npm-dev-server npm-install npm-ci npm-build npm-typecheck npm-lint npm-test
make npm-lint npm-build npm-test npm-typecheck
git diff --check
git status --short --branch
git tag --list
```

## Risks and mitigations

| Risk                                 | Impact | Mitigation                                                                  |
|--------------------------------------|--------|-----------------------------------------------------------------------------|
| Make parses a colon in a target name | Medium | Use `npm-test`; keep `test:fetch` only inside the recipe                    |
| `-T` changes interactive behaviour   | Low    | Only applied to `NODE_CONT`; `sh`/`bash` still use `PHP_CONT` interactively |
| Accidental install/lock churn        | Medium | Never execute `npm-install` / `npm-ci` during verification; dry-run only    |

## Open questions

None.
