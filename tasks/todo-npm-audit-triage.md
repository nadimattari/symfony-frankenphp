# Tasks: npm audit triage (`npm-audit-triage`)

Status: **approved** (2026-11-25) · [plan](plan-npm-audit-triage.md)

Source spec: [SPEC-npm-audit-triage.md](../spec/SPEC-npm-audit-triage.md)

## A1 — Capture audit evidence

**Description:** Run `npm audit --omit=dev --json` and `npm audit --json` in `sf-node`; verify 0 production findings and the full 3 critical / 20 high / 16 moderate / 5 low breakdown.

**Acceptance criteria:**
- [x] Production audit reports 0 vulnerabilities.
- [x] Full audit reports 44 with the expected severity counts.

**Verification:**
- [x] Audit JSON metadata copied to `/tmp/opencode/` for derivation

**Dependencies:** None

**Files likely touched:** None in git

**Estimated scope:** XS

## A2 — Create the triage record

**Description:** Write `docs/security/npm-audit-triage.md` with the finding table, tool groups, reachability rationale, explicit accept/defer decisions, snapshot date and review trigger.

**Acceptance criteria:**
- [x] Every one of the 44 packages appears once.
- [x] Critical, high, moderate and low counts are recorded.
- [x] Production dependencies are explicitly clean; forced upgrades are deferred.

**Verification:**
- [x] Read the document and cross-check package count against audit JSON.

**Dependencies:** A1

**Files likely touched:** `docs/security/npm-audit-triage.md`

**Estimated scope:** M

## A3 — Add README pointer

**Description:** Add one line near the existing deferred-risk paragraph pointing to the triage record.

**Acceptance criteria:**
- [x] No release/auth/CSP text is rewritten.

**Verification:**
- [x] `git diff -- README.md`

**Dependencies:** A2

**Files likely touched:** `README.md`

**Estimated scope:** XS

### Checkpoint: Complete record

- [x] All findings mapped and decisions explicit

## A4 — Verify no dependency churn and rerun gates

**Description:** Confirm `package.json` and `package-lock.json` are unchanged, then rerun all four frontend gates in `sf-node`.

**Acceptance criteria:**
- [x] No manifest or lock diff.
- [x] `lint`, `build`, `test:fetch`, `typecheck` exit 0.

**Verification:**
- [x] `git diff -- package.json package-lock.json`
- [x] `docker compose exec -T sf-node sh -c 'npm run lint && npm run build && npm run test:fetch && npm run typecheck'`

**Dependencies:** A3

**Files likely touched:** `tasks/todo-npm-audit-triage.md` (recorded results only)

**Estimated scope:** S

## A5 — Hygiene and one local commit

**Description:** Stage only this module's files and make one local commit. No push or tag.

**Acceptance criteria:**
- [x] Staged diff contains only the triage document, README pointer and this module's plan/tasks.
- [x] One local commit; clean tree; no push, no new tag.

**Verification:**
- [x] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [x] `git status --short --branch`, `git tag --list`

**Dependencies:** A4

**Files likely touched:** None besides git metadata

**Estimated scope:** XS

### Checkpoint: Complete

- [x] All spec acceptance criteria met; one commit; clean tree; no push, no tag

## Recorded verification results

- `npm audit --omit=dev --json` in `sf-node`: exit 0, 0 vulnerabilities. `npm audit --json`: exit 1, 44 vulnerabilities with metadata `{critical: 3, high: 20, moderate: 16, low: 5, total: 44}`.
- All 44 packages appear exactly once in the triage table and exactly once across the five decision groups (4 + 3 + 18 + 5 + 14). No finding is silently omitted.
- All 44 entries have a `fixAvailable` path; 9 require a semver-major upgrade (Encore 5 or `webpack-notifier` 1.11.1), which is explicitly deferred.
- Reachability record states build-time-only scope, trusted-build assumption, production-clean status and the review trigger (before release/tag decisions and the next toolchain maintenance window).
- `git diff -- package.json package-lock.json` is empty; no `npm audit fix`, `npm update` or lock regeneration was run.
- Frontend gates in `sf-node`: `lint`, `build`, `test:fetch`, `typecheck` all exit 0.
- `git diff --check` clean; staged secret scan showed no literal credential value; one local commit; no push and no new tag.
