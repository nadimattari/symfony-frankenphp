# Implementation Plan: npm audit triage (`npm-audit-triage`)

Status: **approved** (2026-11-25) — covered by the approved capability map, spec and up-front plan-gate decision

Source spec: [SPEC-npm-audit-triage.md](../spec/SPEC-npm-audit-triage.md) · [capability map](../spec/capability-map-deferred-cleanup.md)

## Overview

Record an explicit, reviewable decision for all 44 `npm audit` findings. Production dependencies are clean; every finding is in the build/dev toolchain. No dependency, manifest or lock-file change is made in this initiative.

## Architecture decisions

- Capture `npm audit --json` and `npm audit --omit=dev --json` inside `sf-node` and derive the table from the machine-readable snapshot.
- Group the 44 findings into five tool groups and assign each group a reachability rationale plus an explicit accept/defer decision.
- State that `npm run build` emits static assets only; dev-server, middleware, proxy, websocket, notifier and editor packages are not shipped in the deployed artifact or reached by PHP runtime.
- Keep `package.json` and `package-lock.json` untouched; do not run `npm audit fix`, `npm update` or major bumps.
- Add a one-line README pointer to the new record; do not rewrite release docs, auth specs or CSP specs.

## Task list

### Phase 1: Evidence and document

- [x] A1 — Capture audit snapshots and verify counts/severity breakdown
- [x] A2 — Create `docs/security/npm-audit-triage.md` with findings, reachability and decisions
- [x] A3 — Add the short README pointer

### Checkpoint: Complete record

- [x] All 44 findings mapped; production-clean statement and review trigger present

### Phase 2: Verify and close

- [x] A4 — Confirm no manifest/lock change and rerun frontend gates
- [x] A5 — Hygiene and one local commit, no push or tag

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; no push, no tag

## Verification commands

```bash
docker compose exec -T sf-node npm audit --omit=dev --json
docker compose exec -T sf-node npm audit --json
git diff -- package.json package-lock.json
docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'
git diff --check
```

## Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| A finding is silently ignored | High | Table lists every package and group decision |
| Record overstates reachability | High | State build-time-only scope and the trusted-build caveat plainly |
| Lock churn from audit tooling | Medium | Read-only audit commands; verify manifest/lock unchanged |

## Open questions

None.
