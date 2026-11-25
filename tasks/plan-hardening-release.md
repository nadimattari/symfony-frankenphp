# Implementation Plan: Hardening release (`hardening-release` module)

Status: **approved** (2026-11-25)

Source spec: [SPEC-hardening-release.md](../spec/SPEC-hardening-release.md) · [capability map](../spec/capability-map.md)

Tasks are recorded in [todo-hardening-release.md](todo-hardening-release.md). These are dedicated files: `tasks/plan.md`, `tasks/todo.md`, `tasks/plan-sakai-primevue-ts.md`, `tasks/todo-sakai-primevue-ts.md` and the deliberately open Task 35 are untouched.

## Overview

Close the hardening initiative with documentation and gate work only. The two ADR 0003 blockers are already implemented, tested and pushed; this module prepares consumer-facing `v1.0.0` release content, records current locked versions without inventing a tag, aligns README/ADR/task status with reality, and runs the full existing gate suite. No runtime source, test, configuration, manifest, lock file or tag changes are in scope.

Every task must also clear the standing [Definition of Done](../.opencode/skills/planning-and-task-breakdown/references/shared/definition-of-done.md): acceptance criteria met, scoped changes, existing gates green, documentation truthful, no secrets, and human review before any push.

## Architecture decisions

- **Documentation-only:** no PHP, Vue, Twig, YAML, manifest, lock or test file is edited. Versions come from the current `composer.lock` and `package-lock.json` through read-only commands.
- **Release status wording:** `v1.0.0` is “prepared”, “gate ready” and “tag pending”. The current tagged release remains `v0.2.0`; no `v1.0.0` ref, commit hash or annotation is invented.
- **CHANGELOG:** add a curated `## [v1.0.0] - 2026-11-25` entry above v0.2.0, grouped by impact (`Added`, `Changed`, `Security`) and written for consumers, not as a raw commit log.
- **Version inventory:** add `## v1.0.0 — prepared (2026-11-25), tag pending` above v0.2.0 with resolved versions from the current locks, including `symfony/rate-limiter v7.4.23`; demote v0.2.0 from “current” to the previous tagged release and update the versioning-policy note.
- **ADR history:** append a dated note to ADR 0005 that the ADR 0003 blockers are closed and the 1.0.0 gate is prepared. Status, decision, alternatives and consequences remain unchanged.
- **Task alignment:** check only auth/CSP boxes that are objectively satisfied. Deferred items — Task 35, `npm audit` triage, SPA deep links, proxy/Caddy DoS protection and the `localStorage` ADR revisit — stay open.
- **Gates are evidence, not cleanup:** run the existing commands unchanged. PHPUnit’s known exit 1 and `npm run lint`’s 2586 Prettier warnings are recorded accurately; no assertion, threshold or check is weakened.
- **Increment discipline:** one reviewable commit per docs slice, `git diff --check` and a literal-value secret review before each commit.
- **Branching and release control:** after plan/task approval, create a short-lived `feature/hardening-release` branch from local `main` at `c6415ea`. Merge locally only after review. No push and no tag without explicit human approval; `origin/main` and the existing tag list remain unchanged by this module.

## Task list

### Phase 1: Release metadata

- [x] R1 — Record approved plan/tasks and create the working branch
- [x] R2 — Curate the `v1.0.0` CHANGELOG entry
- [x] R3 — Add the prepared `v1.0.0` version inventory section

### Checkpoint: Release metadata consistent

- [x] CHANGELOG, inventory and spec agree on “prepared / gate ready / tag pending”
- [x] No invented tag hash or claim that `v1.0.0` exists as a ref

### Phase 2: Status alignment

- [x] R4 — Update README release status and append the ADR 0005 note
- [x] R5 — Align satisfied auth/CSP review and push checkboxes

### Checkpoint: Status text consistent

- [x] README, ADR 0005 and task files describe the same release state
- [x] Every deferred item is still visibly open

### Phase 3: Gate and close

- [x] R6 — Run full backend/frontend gates and record honest results
- [x] R7 — Final hygiene, local merge and commit

### Checkpoint: Complete

- [x] All spec success criteria are met
- [x] Clean working tree, no new tag, no push, origin/main unchanged
- [x] Human review requested before any push decision

## Risks and mitigations

| Risk                                                   | Impact  | Mitigation                                                                                                                               |
|--------------------------------------------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------|
| Docs imply `v1.0.0` is already tagged                  | High    | Fixed wording in spec/plan; review every heading and intro for “prepared / gate ready / tag pending”; verify `git tag --list` unchanged  |
| Inventory versions drift from the locks                | High    | Read versions from `composer show --locked` and `npm ls --depth=0`; never hand-invent a release commit hash                              |
| Gate results are overstated                            | Medium  | Record the exact PHPUnit summary/exit cause and lint warning count; do not change config or tests to silence known noise                 |
| Deferred work is accidentally marked complete          | Medium  | R5 has an explicit allowlist of boxes to check and an explicit keep-open list                                                            |
| Secret scan produces keyword false positives           | Low     | Review matched lines for literal values only; no new credentials are added                                                               |
| Accidental push or tag destroys the approval gate      | High    | No push/tag command in the task list; final checkpoint requires explicit human approval                                                  |

## Verification commands

```bash
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
docker compose exec -T sf-php composer validate --no-check-publish
docker compose exec -T sf-node sh -c 'npm run build && npm run test:fetch && npm run lint && npm run typecheck'

docker compose exec -T sf-php composer show --locked --format=json | jq -r '.locked[] | select(.name == "symfony/framework-bundle" or .name == "symfony/security-bundle" or .name == "symfony/rate-limiter") | "\(.name) \(.version)"'
docker compose exec -T sf-node npm ls --depth=0 --json | jq -r '.dependencies | to_entries[] | select(.key == "vue" or .key == "primevue" or .key == "tailwindcss") | "\(.key) \(.value.version)"'

git diff --check
git diff --staged -i -G'password|secret|api_key|token|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE' --name-only
git tag --list
git status --short --branch
git log --oneline --decorate -10
```

Production `npm run build` cleans unversioned dev assets. If the local browser stack will be used again, restore them with `docker compose exec -T sf-node npm run dev` after the gate run.

## Open questions

None. The spec’s release-label questions were answered on 2026-11-25: dated release-ready CHANGELOG entry, prepared/tag-pending inventory section, and a dated appended note in ADR 0005.
