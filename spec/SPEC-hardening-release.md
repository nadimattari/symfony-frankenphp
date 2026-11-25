# Spec: Hardening release — V1 (`hardening-release`)

Status: **approved** (2026-11-25)

Source: [capability map](capability-map.md) · [ADR 0003](../docs/adr/0003-auth-architecture.md) · [ADR 0005](../docs/adr/0005-release-and-branch-strategy.md)

## Objective

Close the approved hardening initiative by preparing the `1.0.0` release documentation and gate. The two ADR 0003 blockers — no auth rate limiting and no CSP — are now implemented, tested and pushed to `origin/main`. This module makes the repository say so in a way a consumer can trust: curated release notes, a resolved version inventory for the prepared release, updated README/ADR status, and a final full-gate run.

Success means a reviewer can answer: “Which release line am I on, what changed to earn 1.0, what versions are locked, and is the gate green?” without reading commit history.

## Tech Stack

Documentation and release metadata only:

- Markdown files under `CHANGELOG.md`, `README.md`, `docs/adr/` and `docs/releases/`.
- Existing lock files are the source of truth for versions; no manifest or lock change is expected except the already-committed `symfony/rate-limiter`.
- Symfony 7.4 LTS / PHP 8.2 / Node 20 remain fixed. No Reprise, Symfony 8 or dependency upgrade in this module.

## Commands

Full verification commands, run from the repository root with the dev stack up:

```bash
# Backend
docker compose exec -e APP_ENV=test -T sf-php vendor/bin/simple-phpunit
docker compose exec -T sf-php composer validate --no-check-publish

# Frontend (authoritative gates; npm test does not exist)
docker compose exec -T sf-node sh -c 'npm run build && npm run test:fetch && npm run lint && npm run typecheck'

# Docs/diff hygiene
git diff --check
git diff --staged -i -G'password|secret|api_key|PRIMEUI_LICENSE_KEY|JWT_PASSPHRASE'
git status --short --branch
git log --oneline --decorate -10
```

Known pre-existing gate noise: PHPUnit prints `OK (58 tests, 375 assertions)` but exits 1 because of indirect deprecations and the `phpunit.xml.dist` `<source>` schema warning; `npm run lint` exits 0 with 2586 Prettier warnings. These must be recorded accurately, not “fixed” by weakening assertions or suppressing checks.

## Project Structure

| Path | Change |
|---|---|
| `CHANGELOG.md` | Add a curated `## [v1.0.0] - 2026-11-25` release-ready entry above v0.2.0 |
| `docs/releases/version-inventory.md` | Add a `v1.0.0 — prepared (2026-11-25), tag pending` section; demote v0.2.0 from “current” to previous release |
| `README.md` | Change the Releases section to name v1.0.0 as prepared / gate-ready, still untagged; link to the new inventory section |
| `docs/adr/0005-release-and-branch-strategy.md` | Append a dated 2026-11-25 note that ADR 0003 residuals are closed and the 1.0.0 gate is prepared; do not rewrite the original decision |
| `tasks/plan-hardening-1.0.md`, `tasks/todo-hardening-1.0.md`, `tasks/plan-hardening-csp.md`, `tasks/todo-hardening-csp.md` | Align now-satisfied review/push checkboxes with reality; leave deferred items unchecked |
| `spec/SPEC-hardening-release.md`, `tasks/plan-hardening-release.md`, `tasks/todo-hardening-release.md` | Dedicated spec/plan/tasks for this module, separate from the shipped auth/CSP artifacts |

No runtime source, test, configuration, manifest or lock file should change in this module.

## Code Style

Markdown conventions already used in the repository:

- ISO dates (`2026-11-25`), `v`-prefixed version headings (`## [v1.0.0] - 2026-11-25`).
- Curated changelog sections grouped by impact: `### Added`, `### Changed`, `### Security`. Do not dump raw commit messages.
- Tables use aligned separators and relative links.
- Release status wording is explicit: “prepared”, “gate ready”, “tag pending”. Never imply `v1.0.0` exists as a ref.
- ADR updates are appended with a dated note; earlier status, decision and alternatives remain intact.

Example of required status wording:

```markdown
## v1.0.0 — prepared (2026-11-25), tag pending

The 1.0.0 gate is satisfied in documentation and full-gate checks. The annotated
tag is a separate decision and has not been created.
```

## Testing Strategy

This module changes no executable behavior, so no new automated tests are added. Verification is documentation review plus the repository’s existing gates:

1. **Content review:** every success criterion below is checked against the diff.
2. **Backend gate:** full `simple-phpunit` suite still reports `OK (58 tests, 375 assertions)`; the known exit-1 cause is unchanged.
3. **Frontend gates:** `build`, `test:fetch`, `lint`, `typecheck` all exit 0; restore unversioned dev assets with `npm run dev` after the production build if the local stack will be used again.
4. **Composer validation:** `composer validate --no-check-publish` passes.
5. **Hygiene:** `git diff --check`, staged secret scan reviewed for literal values only, clean working tree after the final commit, and `origin/main` unchanged by this module until explicit push approval.

## Boundaries

- **Always:** preserve ADR history; derive versions from current lock files; state that the tag is pending; run full gates before committing; keep secrets and raw credentials out of the diff.
- **Ask first:** creating or pushing any tag; changing manifests, lock files, platform versions or runtime code; editing historical release notes; marking deferred work complete.
- **Never:** move/delete an existing tag, write a fake tag commit hash into the inventory, rewrite history, weaken an existing test, or claim `v1.0.0` is released before a tag exists.

## Success Criteria

- [ ] `CHANGELOG.md` has a release-ready `v1.0.0` entry dated 2026-11-25 with curated `Added`, `Changed` and `Security` notes covering auth rate limiting, strict nonce CSP, RED-first tests and browser smoke.
- [ ] `docs/releases/version-inventory.md` has a v1.0.0 section marked `tag pending`, with resolved versions from current lock files (including `symfony/rate-limiter v7.4.23`) and no invented commit hash; v0.2.0 is no longer labelled current.
- [ ] `README.md` names v1.0.0 as prepared/gate-ready and keeps the tag decision separate.
- [ ] ADR 0005 has a short appended 2026-11-25 note; its original decision and alternatives are unchanged.
- [ ] Existing auth/CSP plan and task files have satisfied review/push boxes aligned; Task 35 and other deferred items remain open.
- [ ] Full gates are run and results are recorded truthfully, including pre-existing warnings/exit codes.
- [ ] `git diff --check` and staged secret scan are clean; no tag is created or pushed; local commits are made before any explicit push approval.

## Out of Scope (Deferred)

- Cutting, signing or pushing the annotated `v1.0.0` tag — separate human decision after this gate.
- Symfony Reprise / Vite and Task 35 — November 2027, Symfony 8.4 LTS.
- `npm audit` dev-toolchain advisory triage — separate release task.
- SPA deep-link host rewrite and Caddy/proxy-level DoS protection — deployment concerns.
- Moving the access token from `localStorage` to httpOnly cookies — needs a new ADR.
- Runtime code, dependencies, CI setup and test-framework changes.

## Open Questions

None. The three release-label questions were answered on 2026-11-25: use a dated release-ready CHANGELOG entry, add a prepared/tag-pending inventory section, and append a dated note to ADR 0005.
