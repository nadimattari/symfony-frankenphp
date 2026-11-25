# Spec: npm audit dev-toolchain triage (`npm-audit-triage`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map-deferred-cleanup.md)

## Objective

Produce an explicit, reviewable decision record for the 44 `npm audit` findings so the maintainer can close the audit item with a documented accept/defer position instead of an open-ended upgrade project.

## Current evidence

- `npm audit --omit=dev` reports **0 vulnerabilities**; production dependencies as shipped are clean.
- Full `npm audit` reports **44 findings**: 3 critical, 20 high, 16 moderate, 5 low.
- All findings are in the build/dev toolchain. Direct packages involved are `@babel/core` (low), `@symfony/webpack-encore` (moderate), `webpack` (moderate) and `webpack-notifier` (moderate). Transitive packages span Babel helpers/runtime, webpack-dev-server and dev middleware, Express/body-parser/path-to-regexp, shell-quote/launch-editor/node-notifier, postcss/ajv/svgo/lodash and related utilities.
- Critical findings are `proxy-addr`, `shell-quote` and `websocket-driver`; high findings include `express`, `http-proxy-middleware`, `webpack-dev-server`, `lodash`, `node-forge`, `brace-expansion`, `braces`, `picomatch`, `browserslist`, `ws` and others.

## Approach

- Generate a machine-readable snapshot with `npm audit --json` and derive a compact table of package, severity, direct/transitive status, advisory ID and range.
- Classify reachability: build-time/dev-server only vs anything that can reach a deployed artifact or runtime service. `npm run build` emits static assets; dev-server and middleware packages are not part of the production image. State this reasoning explicitly.
- Decide per tool group: accept (with rationale), defer (with a date/owner), or note as already fixed by a locked version. No forced major upgrades and no lock churn in this initiative.
- Record the result in a new document; link it from the README security section if a short pointer helps future maintainers.

## Commands

```bash
docker compose exec -T sf-node npm audit
docker compose exec -T sf-node npm audit --omit=dev
docker compose exec -T sf-node npm audit --json
git diff --check
```

## Project structure

| Path | Change |
|---|---|
| `docs/security/npm-audit-triage.md` | New decision record: counts by severity, affected tool groups, reachability rationale, explicit accept/defer decisions, snapshot date (2026-11-25) and review trigger |
| `README.md` | Optional one-line pointer to the triage record from the security/deferred-risk section; do not rewrite release docs |
| `spec/SPEC-npm-audit-triage.md`, `tasks/plan-npm-audit-triage.md`, `tasks/todo-npm-audit-triage.md` | Dedicated spec/plan/tasks for this module |

No manifest or lock file change. Do not run `npm audit fix`, `npm update` or dependency major bumps here.

## Acceptance criteria

- [ ] Triage record states both audit commands' results and the 3/20/16/5 severity breakdown.
- [ ] Every finding group is mapped to a reachability rationale and an explicit accept/defer decision; no finding is silently ignored.
- [ ] The decision explicitly says production dependencies remain clean and that forced upgrades are deferred, with a named review trigger.
- [ ] `package.json` and `package-lock.json` are unchanged; frontend gates still exit 0.
- [ ] One local commit containing only this module; no push and no tag.

## Out of scope

Dependency upgrades, lock-file regeneration, adding a new test framework, and any change to the shipped auth/CSP or release docs beyond the optional README pointer.
