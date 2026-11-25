# ADR 0005: Preserve the starter baseline as a release branch and promote the feature line to `main`

## Status

Accepted (2026-11-24)

## Context

`main` pointed at `84550eb` ("Merge pull request #1 from nadimattari/migration/frankenphp-symfony7", 2026-10-26): the FrankenPHP/Symfony 7.4 starter with the older JavaScript Sakai fork on PrimeVue 3. The `feature/primevue-sakai-ts` line was 54 commits ahead of it and contained a new frontend stack (PrimeVue 5, upstream Sakai 5.0.0, strict TypeScript, Tailwind 4), authentication and mail endpoints, plus the corresponding tests and ADRs.

The request was to keep the old `main` contents in a separate branch and tag them, and to make the feature line the new `main`. The repository has a shared `origin/main`; history rewriting or force-pushing would put other clones at risk. The older lightweight tag `pre-migration` (`c2e19cd`, 2026-10-24) also exists and predates the current baseline.

## Decision

1. Keep the old `main` tip immutable as branch **`release/v0.1`** at `84550eb`.
2. Create an annotated tag **`v0.1.0`** at that commit, with a release message naming the stack it captures.
3. **Fast-forward** `main` from `84550eb` to the feature tip (`3423438` at decision time). No squash, no rebase and no merge commit: `main` is a strict descendant of the old tip.
4. Tag the promoted line as annotated **`v0.2.0`** at the release commit on `main`.
5. Keep the existing `pre-migration` tag untouched as historical context; it is not a release.
6. Use semantic versioning. While the project is pre-1.0, a minor release may contain breaking changes; v0.2.0 replaces the frontend stack, so it is a minor, not a patch. `1.0.0` is reserved until the residual auth risks in ADR 0003 are resolved.
7. Record human-readable release notes in `CHANGELOG.md` and resolved framework/library/infrastructure versions in `docs/releases/version-inventory.md`.

## Alternatives considered

### Rename `main` to a legacy branch and create a fresh `main` at the feature tip

- **Pros:** the branch names suggest a clean handover.
- **Cons:** the remote `main` would have to be force-pushed, breaking every existing clone and any CI references. **Rejected** in favour of a fast-forward.

### Merge the feature branch into `main` with a merge commit

- **Pros:** records an explicit promotion point.
- **Cons:** adds a noisy merge commit where a fast-forward is already possible; the tag carries the same information. **Not chosen.**

### Keep only a tag for the old baseline, without a branch

- **Pros:** fewer refs.
- **Cons:** no convenient branch for applying a backport or inspecting the baseline with normal tooling; the user explicitly asked for a branch. **Rejected.**

### Use `v1.0.0` for the promoted line

- **Pros:** signals a stable starter.
- **Cons:** ADR 0003 still lists unresolved security constraints (no CSP, no auth rate limiting); calling that 1.0 would overstate stability. **Deferred.**

## Rationale

A fast-forward keeps the dependency graph honest: the new `main` contains the entire baseline history, and `release/v0.1` remains an exact, reproducible snapshot. Annotated tags carry author, date and message, which makes them a better release source of truth than branch names or hand-edited version files. The two-version scheme gives consumers of this starter an unambiguous answer to "which stack am I using?".

## Consequences

- `origin/main` is updated by a normal fast-forward push; no force-push is needed.
- `release/v0.1` is a frozen branch. Fixes there require a deliberate cherry-pick and a new `v0.1.x` tag, not further work on `main`.
- Tags are immutable: correcting release metadata means creating a new tag, not moving an existing one.
- The feature branch becomes redundant once `main` points to the same commit; it can be deleted locally without losing history.
- Deps/infra versions must be refreshed in `docs/releases/version-inventory.md` when a new release is tagged.

## Revisit

Revisit when a stable 1.0.0 is warranted (ADR 0003 residual risks resolved), when `release/v0.1` needs a backport, or if the project adopts a different release cadence.

## Note (2026-11-25)

The two ADR 0003 blockers that deferred 1.0.0 — no auth rate limiting and no CSP — are now closed: application-level throttling returns JSON 429 with `Retry-After`, and a strict nonce-based CSP is emitted on every main response. The release documentation is prepared, so the 1.0.0 gate is ready. The annotated `v1.0.0` tag has not been created; tagging remains a separate human decision. This note does not change the original decision, alternatives or branch/tag history above; remaining risks are recorded in ADR 0003 as accepted or open.
