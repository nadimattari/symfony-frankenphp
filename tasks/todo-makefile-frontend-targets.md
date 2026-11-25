# Task List: Makefile frontend targets (`makefile-frontend-targets`)

Companion to [plan-makefile-frontend-targets.md](plan-makefile-frontend-targets.md). Spec: [SPEC-makefile-frontend-targets.md](../spec/SPEC-makefile-frontend-targets.md).

## Task M1: Wire the frontend targets

**Description:** Update `Makefile` variables, `.PHONY` and the frontend section so all nine npm-backed commands are 
available through `make` in `sf-node`.

**Acceptance criteria:**
- [ ] `NODE_CONT = $(DOCKER_COMP) exec -T sf-node` and `NPM = $(NODE_CONT) npm`.
- [ ] `sakai` placeholder replaced by `npm-dev`, `npm-watch`, `npm-build`, `npm-dev-server`, `npm-install`, `npm-ci`, 
`npm-typecheck`, `npm-lint`, `npm-test`.
- [ ] Every target has a `##` help line and a silent `@` recipe; all are in `.PHONY`.

**Verification:**
- [ ] `make help` lists the new targets.
- [ ] `make -n` shows the exact expected command for each target.
- [ ] `git diff --check` is clean.

**Dependencies:** None

**Files likely touched:**
- `Makefile`

**Estimated scope:** XS (1 file)

## Task M2: Run the gates and commit

**Description:** Execute the four fast gates via the new targets, verify the diff boundary and commit the module locally.

**Acceptance criteria:**
- [ ] `make npm-lint npm-build npm-test npm-typecheck` all exit 0.
- [ ] Only `Makefile` plus this module's spec/plan/todo files changed.
- [ ] One local commit; no push, no tag.

**Verification:**
- [ ] `git status --short --branch`, `git tag --list`, `git diff --check`.

**Dependencies:** M1

**Files likely touched:**
- none (verification and commit only)

**Estimated scope:** XS

## Recorded results

- `make help` lists all nine frontend targets under a `Frontend (Vue)` section; the `sakai` placeholder is gone.
- `make -n npm-dev npm-watch npm-build npm-dev-server npm-install npm-ci npm-typecheck npm-lint npm-test` printed exactly nine `docker compose exec -T sf-node ...` commands; no install/watch/server command was executed.
- `make npm-lint npm-build npm-test npm-typecheck` exited 0. Lint: 0 problems. Build: compiled with the known harmless `postcss-calc` CSS warning. Frontend tests: 36/36 pass. Typecheck: clean.
- `git diff --check` clean; only `Makefile` plus this initiative's planning artifacts changed.
- Commit scope: one local commit; no push and no tag.
