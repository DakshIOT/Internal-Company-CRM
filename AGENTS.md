# AGENTS.md

## Mission
- Build an internal company CRM in Laravel for admin and employee use only.
- Optimize for premium UI, strong mobile responsiveness, clean architecture, calculation accuracy, venue-based isolation, and maintainability.
- Treat `CRM_BRIEF.md` as the business source of truth and `PROJECT_PLAN.md` as the implementation source of truth.

## Scope Rules
- Do not turn this project into a multi-company SaaS.
- Do not relax venue rules, role rules, or calculation rules for convenience.
- Do not introduce implementation code that conflicts with Hostinger Business shared-hosting constraints.

## Stack Defaults
- Framework: Laravel 11 if Hostinger-compatible at implementation time; otherwise the latest Hostinger-safe Laravel version without changing architecture.
- Frontend: Blade, Livewire 3, Alpine.js, Tailwind CSS, Vite.
- Database: MySQL or MariaDB.
- Auth: Laravel session auth with fixed internal roles.
- Storage: Laravel filesystem on local/public disk with authorized download routes.
- Exports: `maatwebsite/excel`.
- Avoid Redis, Horizon, websockets, SSR, and packages that depend on always-on workers unless explicitly justified and still hosting-safe.

## Fixed Business Invariants
- Employees must follow `Login -> Venue Selection -> Role Dashboard`.
- Employees cannot access dashboards or protected modules until a valid assigned venue is selected.
- Admin may access a global dashboard without mandatory venue selection, but admin reporting must use explicit filters and must not accidentally inherit employee venue session logic.
- Selected venue is stored in session as `selected_venue_id` and must be revalidated against assigned venues on every request.
- Switching venue must fully change the data context for counts, tables, totals, and forms.
- No currency symbol or currency code may appear anywhere in UI, exports, fixtures, seed data, or tests.
- Money must use integer minor units project-wide. Never use floats for business amounts.
- Server-side calculations are authoritative. Do not rely on Blade or JavaScript as the source of truth for stored totals.

## Role Contract
- `admin`: full system access, employee management, venue management, service/package management, vendor naming, admin income, reports, exports, and full visibility.
- `employee_a`: venue selection, dashboard, function entry, daily income, daily billing, frozen fund visibility in function context only.
- `employee_b`: venue selection, dashboard, function entry, daily income, daily billing, vendor entry, no frozen fund.
- `employee_c`: venue selection, dashboard, function entry only.
- Keep roles fixed in code for v1. Do not introduce a dynamic permission builder unless the brief changes.

## Calculation Contract
- `Function Total = Package Total + Extra Charges - Discounts`
- `Paid = Sum of Installments`
- `Pending = Function Total - Paid`
- `Net Total After Frozen Fund = Function Grand Total - Frozen Fund`
- Recalculate function totals transactionally after every create, update, or delete of child records.
- Frozen fund applies only to Employee Type A and only per employee plus venue assignment.

## Domain and Query Rules
- Organize code as a modular monolith around these bounded contexts: `Access`, `MasterData`, `Functions`, `Ledgers`, `Reports`, `Files`, and `Shared`.
- Keep controllers thin. Put business rules in actions, services, policies, form requests, and query objects.
- Scope every employee-facing query by selected venue and authenticated user permissions.
- Use explicit report filter contracts for admin reports and exports.
- Prefer database aggregation for totals and reporting. Do not sum production totals from eager-loaded collections when SQL can do it.
- Ledger modules should prefer shared services, requests, view partials, and query helpers over a generic runtime-configured CRUD layer.

## UI Rules
- Build server-first UI by default.
- Maintain a premium, polished admin shell with left sidebar, sticky top bar, visible venue switcher, responsive cards, responsive tables, and clean forms.
- Every new screen must define desktop, tablet, and mobile behavior before implementation is considered complete.
- Function Entry must use a staged interaction model, not a cluttered single form.
- Keep venue context visible in the shell on every employee-facing page after venue selection.

## File and Attachment Rules
- Use one organized attachment strategy across Function Entry, Extra Charges, Installments, Discounts, Daily Income, Daily Billing, Vendor Entry, and Admin Income.
- Support multiple uploads up to 25 MB per file.
- Allow only approved types: images, pdf, doc/docx, xls/xlsx, csv, plus browser camera capture where supported.
- Never expose raw storage paths without authorization checks.

## Testing Gates
- Every new module requires feature coverage for role access, venue scope, CRUD behavior, and totals where relevant.
- Any change affecting calculations requires tests for zero, normal, and large values.
- Any change affecting reports or exports requires tests for filters, totals, column order, and no-currency-symbol output.
- Any attachment flow requires tests for file-type validation, size limits, upload, download, and record-level authorization.
- UI changes should be reviewed against phone, tablet, and desktop breakpoints before merge.

## Subagent Usage Rules
- Use the architecture subagent before schema, migration, package, or deployment-affecting decisions.
- Use the premium responsive UI subagent before dashboard shell, design system, complex forms, or Function Entry workflow changes.
- Use the auth and permissions subagent before route groups, policies, venue middleware, employee management, or frozen-fund logic changes.
- Use the reports and exports subagent before aggregate queries, reporting filters, workbook exports, or totals refactors.
- Use the QA/review subagent at the end of each phase and before merge to verify venue isolation, permissions, calculations, uploads, exports, and mobile regressions.
- Main agent owns final synthesis. Subagents should be used for bounded specialist work, not unsupervised broad edits.

## Change Discipline
- Read `CRM_BRIEF.md` and `PROJECT_PLAN.md` before making architecture or scope decisions.
- Prefer simple, hosting-safe Laravel patterns over overengineered abstractions.
- Do not simplify business rules unless `PROJECT_PLAN.md` is intentionally updated.
- When implementation details are missing, choose the option that preserves venue isolation, calculation accuracy, premium responsiveness, and Hostinger compatibility.
