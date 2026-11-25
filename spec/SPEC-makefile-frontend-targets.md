# Spec Delta: Makefile frontend targets (`makefile-frontend-targets`)

Status: **proposed** (2026-11-25)

Source: [capability map](capability-map-pre-push-cleanup.md) · read-only reference `/home/nadim/Projects/Lamiral/LamiralApp/Makefile` §Frontend (Vue)

## Objective

Make the everyday `sf-node` commands reachable through `make`, as they are in Lamiral's Makefile, without changing what 
those commands do. Today the Makefile has a `sakai: TODO` placeholder and a `NPM` variable that accidentally points at 
the PHP container.

## Current behavior

- `NODE_CONT = $(DOCKER_COMP) exec sf-node` (interactive exec, no `-T`).
- `NPM = $(PHP_CONT) npm` — would run npm inside `sf-php`, which has no npm.
- `sakai: ## TODO: dev, watch, build` is the only frontend-related target and does nothing.
- README/SPEC.md document raw `docker compose exec -T sf-node npm ...` commands; there is no make wrapper for them.

## New behavior

Replace the placeholder with a `Frontend (Vue)` section and add these targets, each with a `##` help comment and an 
`@`-prefixed recipe:

| Target           | Command run in `sf-node`        | Purpose                                    | Verify by                     |
|------------------|---------------------------------|--------------------------------------------|-------------------------------|
| `npm-dev`        | `npm run dev`                   | One-off dev asset compile                  | `make -n npm-dev`             |
| `npm-watch`      | `npm run watch`                 | Rebuild on change (long-running)           | `make -n npm-watch`           |
| `npm-build`      | `npm run build`                 | Production asset build                     | run target                    |
| `npm-dev-server` | `npm run dev-server`            | Webpack dev server (long-running)          | `make -n npm-dev-server`      |
| `npm-install`    | `npm install`                   | Install dependencies (may change lock)     | `make -n npm-install`         |
| `npm-ci`         | `npm ci`                        | Reproducible install from lock             | `make -n npm-ci`              |
| `npm-typecheck`  | `npm run typecheck`             | `vue-tsc --noEmit`                         | run target                    |
| `npm-lint`       | `npm run lint`                  | ESLint/Prettier gate                       | run target                    |
| `npm-test`       | `npm run test:fetch`            | Frontend service/guard tests               | run target                    |

- Set `NODE_CONT = $(DOCKER_COMP) exec -T sf-node` so recipes are non-interactive and work from scripts.
- Set `NPM = $(NODE_CONT) npm` so the variable no longer targets `sf-php`.
- Add all new targets (and `bash`) to `.PHONY`.
- `npm-test` is the make target name because a literal `test:fetch:` rule would be parsed by Make as a `test` target 
- with a `fetch` prerequisite. Its recipe still calls `npm run test:fetch`.

No `package.json`, `package-lock.json`, compose file, CI file or application source changes.

## Commands

```bash
make help
make -n npm-dev npm-watch npm-dev-server npm-install npm-ci
make npm-lint npm-build npm-test npm-typecheck
git diff --check
git status --short --branch
```

## Project structure

| Path                                                                                                                           | Change                                                                          |
|--------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------|
| `Makefile`                                                                                                                     | Variables, `.PHONY`, replace `sakai` placeholder with the nine frontend targets |
| `spec/SPEC-makefile-frontend-targets.md`, `tasks/plan-makefile-frontend-targets.md`, `tasks/todo-makefile-frontend-targets.md` | Dedicated planning artifacts                                                    |

## Code style

Follow the existing help-comment convention exactly:

```make
## —— Frontend (Vue) 🌊 ————————————————————————————————————————————————————————————————
npm-build: ## Run the production asset build in sf-node
	@$(NODE_CONT) npm run build
```

Recipe bodies stay one line, silent (`@`), and use the container variables rather than raw `docker compose` invocations.

## Testing strategy

- This is a tooling/configuration change with no new application behaviour, so no new unit test is added.
- Prove correctness with `make help` (targets and help text visible), `make -n` (exact command for long-running/install 
targets without executing them), and by running the four fast gates through their new targets.
- Baseline is the already-green frontend gate set: `lint`, `build`, `test:fetch`, `typecheck` all exit 0 in `sf-node`.

## Boundaries

- **Always:** run commands in `sf-node` with `-T`; keep help comments; verify dry runs before real runs.
- **Ask first:** adding dev/test/prod compose lifecycle targets, a cache-warm alias, or any change to `package.json` 
scripts.
- **Never:** run `npm install` or `npm ci` during verification (lock/node_modules churn), start a long-running 
watch/server target, or change the shipped platform.

## Acceptance criteria

- [ ] `make help` lists all nine new targets with meaningful descriptions; `sakai` is gone.
- [ ] `make -n` shows the exact expected command in `sf-node` for each target; `NPM` resolves to the node container.
- [ ] `make npm-lint npm-build npm-test npm-typecheck` completes with all four gates exit 0.
- [ ] `git diff` touches only `Makefile` plus this module's spec/plan/task files.
- [ ] One local commit; no push and no tag.

## Out of scope

- Lamiral's dev/test/prod compose variants, `cw` cache-warm alias, and invoice/console targets.
- Dependency installs/upgrades, lock churn, new npm scripts, CI changes, and edits to Lamiral itself.

## Open questions

None.
