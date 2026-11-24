# ADR 0004: Mail backend with testing redirect and a minimal MailRun lifecycle

## Status

Accepted (2026-11-24)

## Context

The starter installs `symfony/mailer` and runs an `axllent/mailpit` container in development, but has no mail configuration yet: `MAILER_DSN` is commented out in `.env`, `config/packages/mailer.yaml` consumes `%env(MAILER_DSN)%` lazily, and no send path exists. Any send would currently fail with an unresolved env var. Mailpit's SMTP port is available inside `sf-net`, its web UI is published at `http://localhost:8125`, and `compose.prod.yaml` never enables its `dev` profile.

ADR 0002 decided to reuse Lamiral's **mail backend** as a read-only structural reference while excluding its report, invoice, pricing and Dropbox layers. `docs/analysis-lamiral-sakai-upgrade.md` §3.3 names the reusable pieces (`MailRouter`, `MailRun` lifecycle, mail settings endpoint, dev Mailpit) and the domain-specific pieces that must not be copied (`ReportMailer`, `src/Service/Report/*`, `src/Service/Invoice/*`, `src/Service/Pricing/*`).

Lamiral's own design sends mail synchronously inside two console commands (`app:mail:statements`, `app:mail:factors`) that are launched as **detached OS processes** from an HTTP controller via `MailRunDispatcher` (`setsid nohup ... &`, built with `symfony/process`). That is a good pattern for a busy production application but a poor fit here: this starter does not install `symfony/process`, has no worker supervision, and needs deterministic tests.

The goal is a generic, testable mail path: one place that applies a non-production redirect, a small durable record of sends, and a way to prove delivery in dev — without the application domain or an implicitly started worker.

Constraints:

- Symfony 7.4 LTS / PHP 8.2 / MariaDB 11.3.2; no platform upgrade.
- No `symfony/process` dependency and no detached worker started implicitly.
- No secrets or real recipient lists in tracked files; environment-only configuration, as for JWT and the PrimeUI license.
- Existing conventions: typed PHP services, Doctrine entities/repositories with migrations, TypeScript frontend services using bearer-authenticated `/api` calls.

## Decision

### Transport and configuration

1. Add `MAILER_DSN=smtp://sf-mailer:1025` to `.env` for development and set `MAILER_DSN=null://null` in `.env.test` so tests never contact SMTP. Real deployments override the DSN through the environment. Keep Mailpit dev-only.
2. Wire `MAIL_FROM`, `MAIL_REPLY_TO`, `MAIL_REDIRECT_TO` and `MAIL_THROTTLE_SECONDS` through `config/services.yaml` as `app.mail_*` parameters, each with an explicit empty/default env parameter so an unset variable is harmless (the same technique used for `PRIMEUI_LICENSE_KEY`). Defaults: empty from/reply-to/redirect and `0` seconds throttle; no real addresses are committed.

### Sending: MailRouter

3. `App\Service\Mail\MailRouter` is a **pure configuration helper**, matching Lamiral: it resolves the from/reply-to/redirect/throttle values and computes per-message behaviour. It does not send mail.
4. When `MAIL_REDIRECT_TO` is non-empty, every message is redirected and marked as a test:
   - `effectiveRecipient($recipient)` returns the redirect address instead of the requested recipient;
   - `rewriteSubject($subject, $originalRecipient)` returns `[TEST] <subject> (for <original>)`;
   - `headers($originalRecipient)` adds `X-Original-Recipient: <original>` so the redirect does not hide who would have received the message.
   With no redirect, all three are no-ops. Sent messages also carry `Auto-Submitted: auto-generated` and a generated `Message-ID`.
5. Lamiral's domain-specific `MAIL_FACTORS_RECIPIENTS` list and `factorsRecipients()` parser are **not** ported.

### Durability: MailRun and EmailLog

6. `MailRun` records one send run with a generic `type` (the starter uses `test`; domain types such as `statement`/`factors` stay out of scope). Statuses follow Lamiral: `queued`, `running`, `completed`, `failed`. Fields: type, status, scheduled period/subject metadata as needed, counters (`sent`, `skipped`, `failed`), `message`, and `createdAt`/`startedAt`/`finishedAt`. Indexes on `(type, created_at)` and `status`.
7. `MailRunManager` creates runs, refuses a second active run of the same type by throwing `ActiveMailRunException`, claims a queued run (`queued` → `running`, setting `startedAt`), updates counters, marks failures, and reaps stale runs. The lamiral thresholds are kept as defaults: queued older than **120 s**, running with no progress older than **7200 s**. This is an application-level guard (no `SELECT ... FOR UPDATE` in the reference); claim additionally re-checks the status so a stale claim cannot start twice.
8. `EmailLog` records one row per attempted message: run reference, a unique `dedupeKey`, the real recipient, the effective recipient (after redirect), status (`sent`/`failed`/`skipped`), `attempts`, `messageId`, `error`, `createdAt` and `sentAt`. No message bodies, attachments or customer foreign keys — Lamiral's `Customer` FK is replaced by plain recipient strings. Re-sends update the same row via an upsert that increments `attempts`.

### Dispatch and throttle

9. `MailRunDispatcher` performs sends **synchronously** through `MailerInterface` and `MailRouter`. It claims the run, sends each message, records/logs the result, applies the throttle between messages, then marks the run `completed` (or `failed` on an unexpected exception). Per-recipient failures are logged and counted; the run can complete with `failed > 0`, matching Lamiral.
10. `MAIL_THROTTLE_SECONDS` is an integer number of seconds between consecutive sends, clamped to `>= 0`, applied after every message except the last. Lamiral's `--sleep` CLI override is not ported because there is no worker command; change the env value instead. No automatic retry beyond the log/upsert semantics in item 8.
11. No `symfony/process`, no `setsid nohup`, no log-per-run file, and no detached worker. A future asynchronous backend should be a separate decision (Messenger is the likely candidate), not an implicit default.

### Endpoints and dev verification

12. `GET /api/mail/settings` (requires `ROLE_USER`) returns the from/reply-to/redirect/throttle values needed by the typed frontend to show a test-mode banner.
13. `POST /api/mail/test` (requires `ROLE_USER`) creates a `test` run, dispatches one short message, and returns the run id/status. The recipient is the supplied address, or `test@example.test` when omitted. Dev and `test` are safe without a redirect: dev sends to Mailpit and `test` uses the null transport. **Outside `dev`/`test` the endpoint returns 503 unless `MAIL_REDIRECT_TO` is set**, avoiding accidental real delivery from a starter endpoint. Enabling the endpoint there requires a real `MAILER_DSN`, a configured `MAIL_FROM`, and a dedicated redirect inbox set in the environment (or gitignored `.env.local`).
14. Dev sends go to Mailpit and are inspected at `http://localhost:8125`. Tests use `MAILER_DSN=null://null` and can replace the transport with a recording spy.

### Explicit exclusions

15. Do not port `ReportMailer`, `XlsxRenderer`, `StatementReportService`, `FactorsReportService`, report templates, `MAIL_FACTORS_RECIPIENTS`, report/invoice commands, or the `/api/mail/statements`, `/api/mail/factors`, `/api/mail/runs*` routes. No attachments, no spreadsheet/PDF dependencies, no customer/sale selection, no Dropbox sync.

## Lamiral reference mapping

| Lamiral (read-only) | This starter | Reason |
|---|---|---|
| `MailRouter.php` (config helper, redirect/subject/headers, `factorsRecipients()`) | Port the helper; drop `factorsRecipients()` and `MAIL_FACTORS_RECIPIENTS` | Domain-specific recipient list |
| `MailRunManager.php` (create/claim/counters/markFailed/reap) | Port minimal version with a generic `test` type and re-check on claim | Keep lifecycle, remove report types |
| `MailRunDispatcher.php` (`Process`, `setsid nohup ... &`, log files) | Replace with a synchronous dispatcher using `MailerInterface`; no `symfony/process` | No worker supervision; deterministic tests |
| `ActiveMailRunException.php` | Port, with a generic message | One active run per type |
| `MailRun.php` / `EmailLog.php` + repositories | Minimal entities: no `Customer` FK, no period/report-specific dedupe formats | Domain decoupling |
| `ReportMailer.php` (only class calling `MailerInterface`) | Fold its generic redirect/header logic into the dispatcher | Attachments and report services excluded |
| `MailController.php` (7 report routes, `ROLE_ADMIN`) | Only `GET /api/mail/settings` and `POST /api/mail/test`, `ROLE_USER` | Starter scope; auth already uses `ROLE_USER` for `/api` |
| `EmailStatementsCommand.php`, `EmailFactorsCommand.php` | Not ported | Domain selection, workbooks, QS validation, report copy |
| Dev `smtp://lamiral-mailer:1025`, test `null://null`, Mailpit UI `:8125` | Same shape with `sf-mailer` | Proven dev/test setup |
| `docs/email-reports.md` UAT/deployment phases | Not ported | Lamiral operational detail |

## Alternatives considered

### Port Lamiral's mail backend verbatim

- **Pros:** preserves the full lifecycle, detached worker and report workflow.
- **Rejected:** pulls in `symfony/process`, detached processes, domain recipient lists, report builders and attachments that this starter does not need.

### Use plain `symfony/mailer` with no MailRun/EmailLog

- **Pros:** smallest possible change.
- **Rejected:** no durable evidence of a send, no redirect with an audit trail, and no throttle; the mail checkpoint could not be demonstrated.

### Queue mail with Symfony Messenger

- **Pros:** standard retry/failure handling and a real async worker.
- **Rejected for now:** adds transport and worker configuration beyond scope. Revisit if sends become slow or high-volume.

### Use an external transactional email provider

- **Pros:** deliverability and monitoring handled externally.
- **Rejected:** requires credentials and an external dependency; Mailpit already covers development verification.

## Consequences

- Two new tables, four environment parameters, one `MAILER_DSN` fix, and two authenticated API endpoints.
- Tests can assert redirect, subject rewrite and original-recipient headers before the transport sees a message, that a claimed run cannot be claimed twice, and that a test send lands in Mailpit in dev.
- Non-dev/test sends are only possible through explicitly configured `MAILER_DSN` and `MAIL_REDIRECT_TO`; the starter's test endpoint refuses to send without a redirect outside `dev`/`test`.
- Real recipient lists, attachments, templates and domain workers remain absent by design; adding them requires a new decision.

## Verification (2026-11-24)

- Dev: `GET /api/mail/settings` returned 200 with `testMode: true`; an authenticated `POST /api/mail/test` returned `status: completed` and `sent=1`. The message appeared in Mailpit at `http://localhost:8125` with subject `Mail smoke test`, from `no-reply@example.test`, addressed to the requested recipient.
- Database: one `mail_run` row (`test`, `completed`, `sent=1`) and one `email_log` row (`sent`, `attempts=1`, generated `Message-ID`); no message body was persisted.
- PHPUnit covers redirect subject/header rewriting, dedupe upserts, run claim/reaping and both API endpoints; PHP tests use the null transport and a recording spy, never SMTP.

## Revisit

Revisit when any of the following changes:

- mail must be truly asynchronous or retried across workers (consider Messenger);
- the starter needs attachments, templates or per-recipient domain logic;
- deliverability/analytics requirements justify an external provider.

## Sources

- `docs/adr/0002-primevue-version-and-sakai-strategy.md` §Decision (mail backend in scope)
- `docs/analysis-lamiral-sakai-upgrade.md` §3.3 (reusable vs domain-specific mail pieces)
- Lamiral reference (read-only): `src/Service/Mail/{MailRouter,MailRunManager,MailRunDispatcher,ActiveMailRunException,ReportMailer}.php`, `src/Entity/{MailRun,EmailLog}.php`, `src/Repository/{MailRunRepository,EmailLogRepository}.php`, `src/Controller/MailController.php`, `src/Command/Email{Statements,Factors}Command.php`, `config/services.yaml`, `.env`, `.env.test`, `compose.prod.yaml`
