# Capability Map: Pre-push cleanup

Status: **proposed** (2026-11-25)

Source: user request before push/tag · [Lamiral Makefile reference](../docs/analysis-lamiral-sakai-upgrade.md) (read-only at `/home/nadim/Projects/Lamiral/LamiralApp`)

## Objective

Close two small, independently verifiable cleanup items before any push or tag. These are new follow-up changes; the nine already-committed cleanup modules and their release notes stay untouched. Task 35 / Symfony Reprise stays pending until November 2027.

Resolved scope decisions (2026-11-25):

- Makefile: frontend/npm targets only, modeled on Lamiral's frontend section. No new compose environment variants or cache-warm alias.
- Roles: remove `ROLE_CASHIER` / `ROLE_CUSTOMER` from live code and config, add a short dated note to ADR 0003, and leave historical task logs and shipped release docs unchanged.
- Auth store: remove the now-unused `isStaff` / `isCustomer` getters; keep admin-authorises-all in `isAuthorisedFor()`.

## Modules

| Module id                    | Responsibility                                                                                                | Depends on  | Independently verifiable by                                                                                                |
|------------------------------|---------------------------------------------------------------------------------------------------------------|-------------|----------------------------------------------------------------------------------------------------------------------------|
| `makefile-frontend-targets`  | Add useful `sf-node` npm/Makefile targets and fix the node-container variable                                 | none        | `make help` lists the targets; each npm-backed target invokes the expected command in `sf-node`; existing gates stay green |
| `remove-lamiral-roles`       | Remove the Lamiral-specific cashier/customer roles from the entity, UI store, hierarchy and test references   | none        | Repo-wide search finds no live `ROLE_CASHIER` / `ROLE_CUSTOMER` references; PHPUnit and all frontend gates stay green      |

Build order: **`makefile-frontend-targets` → `remove-lamiral-roles`**. The modules do not depend on each other; the fixed order keeps review and verification linear. `remove-lamiral-roles` is second because it touches behaviour (auth logic and role hierarchy), not just tooling.

## Proposed defaults

- One local commit per module, with a descriptive `chore:` / `refactor:` message. **No push and no tag without explicit approval.**
- Platform stays fixed: Symfony 7.4 LTS, PHP 8.2, Node 20. No dependency, lock-file, compose-file or CI change.
- `makefile-frontend-targets`: add `npm-dev`, `npm-watch`, `npm-build`, `npm-dev-server`, `npm-install`, `npm-ci`, `typecheck`, `lint` and `test:fetch`; replace the `sakai: TODO` placeholder; point `NPM` at `NODE_CONT` and use `-T` for non-interactive `exec`. Verify by dry-running every target and running `make help`.
- `remove-lamiral-roles`: delete the two constants from `src/Entity/User.php`; delete the two constants plus the `isStaff` / `isCustomer` getters and the cashier special case from `assets/store/auth.ts`; simplify `security.yaml` to `ROLE_ADMIN → ROLE_USER`; change the guard test's arbitrary role literal to a generic role; add a dated note to `docs/adr/0003-auth-architecture.md`. Do not rewrite the ADR's original decision text or old task logs.
- Dedicated delta spec, plan and task files under `spec/` and `tasks/`; never overwrite the shipped capability maps, auth/CSP specs or Task 35 records.

## Out of scope

- Pushing `main`, creating `v1.0.0`, or any other tag.
- Task 35 (Symfony Reprise / Vite) — November 2027.
- New dependencies, npm upgrades, lock churn, new frontend test framework, or changes to `package.json` scripts.
- Changing the JWT, refresh-cookie, CSP or rate-limit contracts.
- Lamiral dev/test/prod compose targets, cache-warm alias, and edits to Lamiral itself.
- Rewriting historical ADR decisions, task logs or the prepared 1.0.0 release notes.

## Gate

Approve or correct the module boundaries, dependency direction, build order and proposed defaults. After approval, each module gets its own small delta spec through the Specify → Plan → Tasks → Implement cycle.
