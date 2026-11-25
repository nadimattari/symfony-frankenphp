# Tasks: Hardening release (`hardening-release` module)

Status: **approved** (2026-11-25) · [plan](plan-hardening-release.md)

Spec: [SPEC-hardening-release.md](../spec/SPEC-hardening-release.md). These tasks are additive: `tasks/plan.md`, `tasks/todo.md`, the auth/CSP artifacts and the Sakai plan with its deliberately open Task 35 are not overwritten. Every task also clears the standing [Definition of Done](../.opencode/skills/planning-and-task-breakdown/references/shared/definition-of-done.md).

## R1 — Record approved plan/tasks and create the working branch

**Description:** After plan/task approval, mark both files approved, create `feature/hardening-release` from local `main` at `c6415ea`, and commit only the planning artifacts. Do not push.

**Acceptance criteria:**
- [x] `tasks/plan-hardening-release.md` and `tasks/todo-hardening-release.md` have status `approved` with the 2026-11-25 date.
- [x] The branch is created from `c6415ea` with no rewrite, rebase or force-push.
- [x] The commit contains only planning docs; no runtime, manifest or lock file is staged.

**Verification:**
- [x] `git branch --show-current` → `feature/hardening-release`
- [x] `git log --oneline -1` shows the planning commit
- [x] `git diff --check` and `git status --short --branch`

**Dependencies:** None

**Files likely touched:** `tasks/plan-hardening-release.md`, `tasks/todo-hardening-release.md`

**Estimated scope:** XS

## R2 — Curate the `v1.0.0` CHANGELOG entry

**Description:** Add a consumer-facing `v1.0.0` entry above v0.2.0 that explains why the hardening work earns the stable version, while making clear that the tag is a later decision.

**Acceptance criteria:**
- [x] Heading is `## [v1.0.0] - 2026-11-25` and appears above v0.2.0.
- [x] Intro says the release content is prepared / gate ready and the annotated tag is pending; no tag hash or fake ref is implied.
- [x] `Added`, `Changed` and `Security` notes cover auth rate limiting, strict nonce CSP, local demo media, RED-first tests, browser smoke and release docs without dumping raw commits.

**Verification:**
- [x] Read the rendered diff for impact-oriented wording and relative links
- [x] `git diff --check`

**Dependencies:** R1

**Files likely touched:** `CHANGELOG.md`

**Estimated scope:** S

## R3 — Add the prepared `v1.0.0` version inventory section

**Description:** Add a prepared-release inventory from the current lock files, demote v0.2.0 from current status, and update the versioning-policy note.

**Acceptance criteria:**
- [x] New section is `## v1.0.0 — prepared (2026-11-25), tag pending` above v0.2.0.
- [x] Backend, frontend and infrastructure tables match the current locks, including `symfony/rate-limiter v7.4.23`; no release commit hash is invented.
- [x] v0.2.0 heading says previous tagged release; intro and versioning policy state that the two ADR 0003 blockers are closed and tagging is pending.

**Verification:**
- [x] Compare against `composer show --locked --format=json` and `npm ls --depth=0 --json`
- [x] Read the section for “tag pending” wording and no fake hash
- [x] `git diff --check`

**Dependencies:** R2

**Files likely touched:** `docs/releases/version-inventory.md`

**Estimated scope:** M

### Checkpoint: Release metadata consistent

- [x] CHANGELOG and inventory agree with the spec’s prepared/gate-ready/tag-pending language
- [x] No `v1.0.0` ref appears in `git tag --list`

## R4 — Update README release status and append the ADR 0005 note

**Description:** Make the README’s Releases section point consumers at the prepared 1.0.0 line while naming v0.2.0 as the latest tag, and append a short dated note to ADR 0005 without rewriting its original decision.

**Acceptance criteria:**
- [x] README names v1.0.0 as prepared / gate-ready with tag pending, keeps v0.2.0 as the current tagged release, and links to the new inventory section.
- [x] ADR 0005 has an appended `## Note (2026-11-25)` stating that the ADR 0003 blockers are closed and the gate is prepared; status, decision, alternatives and consequences are unchanged.
- [x] No sentence claims an annotated `v1.0.0` tag exists.

**Verification:**
- [x] Read the diff for relative links and unchanged ADR history
- [x] `git diff --check`

**Dependencies:** R3

**Files likely touched:** `README.md`, `docs/adr/0005-release-and-branch-strategy.md`

**Estimated scope:** S

## R5 — Align satisfied auth/CSP review and push checkboxes

**Description:** Check only the auth/CSP review, push and follow-on gates that are now objectively satisfied; leave every deferred item unchecked.

**Acceptance criteria:**
- [x] Auth plan/todo boxes for clean tree, no `symfony/lock`/secret/weakened test, human review, push decision and CSP-spec follow-on are checked.
- [x] CSP plan/todo status lines say merged and pushed; review/push and `hardening-release` follow-on boxes are checked.
- [x] Task 35, `npm audit` triage, SPA deep links, proxy/Caddy DoS protection and the `localStorage` ADR revisit remain open/unchecked.

**Verification:**
- [x] Review only the four intended task files in `git diff`
- [x] `git diff --check`

**Dependencies:** R4

**Files likely touched:** `tasks/plan-hardening-1.0.md`, `tasks/todo-hardening-1.0.md`, `tasks/plan-hardening-csp.md`, `tasks/todo-hardening-csp.md`

**Estimated scope:** S

### Checkpoint: Status text consistent

- [x] README, ADR 0005 and task files describe the same release state
- [x] Deferred work is still visibly open

## R6 — Run full backend/frontend gates and record honest results

**Description:** Run the repository’s existing gates unchanged and record their outcomes in this task file. No test, threshold or config is edited to hide known noise.

**Acceptance criteria:**
- [x] Full PHPUnit prints `OK (58 tests, 375 assertions)`; exit 1 is recorded as the unchanged indirect-deprecation / `<source>` schema warning cause.
- [x] `npm run build`, `npm run test:fetch`, `npm run lint` and `npm run typecheck` all exit 0; lint’s 2586 pre-existing warnings and 0 errors are recorded.
- [x] `composer validate --no-check-publish` passes and dev assets are restored with `npm run dev` if the local browser stack will be used again.

**Verification:**
- [x] Run every command in the plan’s Verification commands section
- [x] `git diff --check`

**Dependencies:** R5

**Files likely touched:** `tasks/todo-hardening-release.md`

**Estimated scope:** XS

**Recorded gate results (2026-11-25):**

- Full PHPUnit: `OK (58 tests, 375 assertions)`; process exited 1 only for the pre-existing `phpunit.xml.dist` `<source>` schema warning and 17 indirect `eraseCredentials()` deprecation notices. No test failure.
- `npm run build && npm run test:fetch && npm run lint && npm run typecheck`: exit 0; `npm run lint` reported 2586 pre-existing Prettier warnings and 0 errors.
- `composer validate --no-check-publish`: `./composer.json is valid`.
- `npm run dev` restored unversioned dev assets after the production build.

## R7 — Final hygiene, local merge and commit

**Description:** Commit the module in reviewable slices, verify no secret literal or whitespace error, merge locally without rewriting history, and stop before any push or tag.

**Acceptance criteria:**
- [ ] Staged secret scan is reviewed for literal values only; matched keywords are explained, not silently deleted.
- [ ] Working tree is clean after the final commit and local `main` contains the module by fast-forward.
- [ ] `origin/main` is unchanged, `git tag --list` still has only `pre-migration`, `v0.1.0` and `v0.2.0`, and no `v1.0.0` tag is created.

**Verification:**
- [ ] `git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only`
- [ ] `git status --short --branch`, `git tag --list`, `git log --oneline --decorate -10`
- [ ] `git checkout main && git merge --ff-only feature/hardening-release`

**Dependencies:** R6

**Files likely touched:** None

**Estimated scope:** XS

### Checkpoint: Complete

- [ ] All spec success criteria are met and gate results are recorded truthfully
- [ ] Clean working tree, no new tag, no push, origin/main unchanged
- [ ] Human review requested before any push decision
