# PROJECT_PLAN.md

## Executive Summary
- Build a Laravel-based internal CRM for one company with four fixed user roles: `admin`, `employee_a`, `employee_b`, and `employee_c`.
- Use a modular monolith with Blade, Livewire 3, Alpine.js, Tailwind CSS, and Vite.
- Require venue selection for employees before dashboard access. Venue context controls all employee-facing records, listings, counts, filters, and totals.
- Keep the UI premium and highly responsive across mobile, tablet, and desktop.
- Keep deployment compatible with Hostinger Business shared hosting.
- Keep financial display symbol-free: show plain numeric values only.

## Finalized Defaults
- Admin can access the global dashboard without mandatory venue selection.
- Employees must always select a valid assigned venue before entering protected dashboards or modules.
- Vendor names are venue-scoped with exactly four configurable slots per venue.
- Amounts use integer minor units across the system.
- Audit/history is deferred until after core modules and reports are stable.
- Exports run synchronously or chunked without requiring queue workers.

## Recommended Architecture

### Application Structure
- Use a modular monolith organized by bounded context:
  - `Access`
  - `MasterData`
  - `Functions`
  - `Ledgers`
  - `Reports`
  - `Files`
  - `Shared`
- Keep controllers thin and move business rules into:
  - policies
  - form requests
  - actions
  - services
  - query/filter objects

### Access Model
- Use Laravel auth with a fixed `UserRole` backed enum.
- Add an `EnsureVenueSelected` middleware for employee route groups.
- Store active employee venue in session as `selected_venue_id`.
- Revalidate the session venue against the employee's assigned venues on every request.

### Frontend Model
- Use Blade layouts and components for the application shell.
- Use Livewire for interactive CRUD screens, filters, calculations preview, and dashboard widgets where appropriate.
- Use Alpine.js for lightweight UI state such as drawers, modals, tab panels, and upload interactions.
- Use Tailwind tokens and component conventions to maintain a consistent premium admin UI.

## Domain Map

### Access
- Authentication
- Venue selection and switching
- Role-aware dashboards
- Employee activation and credential management

### Master Data
- Venues
- Employees
- Services
- Packages
- Package-service mapping
- Venue vendor slots
- Employee-to-venue assignments
- Employee/venue service availability
- Employee/venue package availability
- Frozen fund assignment per employee plus venue

### Functions
- Function base entry
- Package totals
- Service line items
- Extra charges
- Installments
- Discounts
- Attachments linked to all function-related records
- Recalculation service for totals

### Ledgers
- Daily income
- Daily billing
- Vendor entry
- Admin income
- Shared attachment handling
- Shared date total and grand total patterns

### Reports
- Admin dashboard rollups
- Module reports
- Cross-module totals
- Employee-wise totals
- Venue-wise totals
- Service and package totals
- Excel exports using shared filters

### Files
- Upload validation
- File storage path conventions
- Authorized viewing/download
- Shared attachment metadata

### Shared
- Money formatting without currency symbols
- Date range filters
- Reusable tables, cards, forms, drawers, and modals
- Common policy and query helpers

## Data Model Direction

### Core Tables
- `users`
  - auth identity
  - role enum
  - active/inactive status
- `venues`
  - venue master data
  - active/inactive status
- `venue_user`
  - user-to-venue assignment
  - frozen fund amount for eligible Type A employees at that venue
- `services`
  - service master
  - standard rate
- `packages`
  - package master
- `package_service`
  - package-to-service mapping
  - optional default ordering
- `service_assignments`
  - employee plus venue plus service availability
- `package_assignments`
  - employee plus venue plus package availability
- `venue_vendors`
  - exactly four vendor slots per venue
  - editable names managed by admin

### Transaction Tables
- `function_entries`
  - base function record
  - snapshot totals: package total, extra charge total, discount total, final total, paid total, pending total
- `function_packages`
  - chosen package records within a function
- `function_service_lines`
  - service row details such as persons, rate, extra charge, note, selected state, line total
- `function_extra_charges`
- `function_installments`
- `function_discounts`
- `daily_income_entries`
- `daily_billing_entries`
- `vendor_entries`
- `admin_income_entries`
- `attachments`
  - polymorphic attachment table for all uploadable modules

### Deferred Table
- `activity_logs`
  - optional phase-6 audit/history enhancement if needed

## Calculation Rules
- `Line Total = (Persons x Rate) + Extra Charge`
- `Function Total = Package Total + Extra Charges - Discounts`
- `Paid = Sum of Installments`
- `Pending = Function Total - Paid`
- `Net Total After Frozen Fund = Function Grand Total - Frozen Fund`
- Recalculate totals server-side inside transactions after child-record writes.
- Do not store or compute money with floats.

## UI and UX Plan
- Use a premium application shell:
  - left sidebar on desktop
  - sticky header
  - visible venue switcher
  - responsive KPI cards
  - filters above listings
  - drawers/modals for focused actions
- Keep role dashboards distinct by module emphasis, not by unrelated duplicated layouts.
- Design Function Entry as a staged workflow:
  - create base function entry
  - open action center
  - manage Packages, Extra Charges, Installments, and Discounts through tabs or segmented panels
  - keep summary totals visible throughout
- Provide mobile-friendly tables that collapse into card-like rows or stacked detail views where needed.

## Reporting and Export Plan
- Restrict reports and exports to admin.
- Use one shared filter contract:
  - venue
  - employee
  - employee type
  - module
  - date range
  - vendor where relevant
  - service where relevant
  - package where relevant
- Use database aggregates for totals and trend queries.
- Use `maatwebsite/excel` for `.xlsx` exports.
- Include totals rows or summary sheets where useful.
- Keep all export formatting free of currency symbols and currency number formats.

## Phased Build Order

### Phase 0: Repository Guidance
- Create `AGENTS.md`
- Create `PROJECT_PLAN.md`
- Create `.agents/skills/*/SKILL.md`
- Lock defaults, architecture, domain boundaries, and review discipline

### Phase 1: Foundation
- Scaffold Laravel application
- Set up auth flow
- Add fixed role enum
- Add venue selection and switching
- Add `EnsureVenueSelected` middleware
- Build premium responsive app shell
- Add base role dashboards and navigation

### Phase 2: Master Data
- Employee management
- Venue management
- Venue assignment
- Frozen fund assignment
- Service management
- Package management
- Package-service mapping
- Venue vendor slot management
- Employee/venue service and package assignment

### Phase 3: Function Entry Core
- Base function entry CRUD
- Function action center
- Package/service line entry flow
- Extra charges
- Installments
- Discounts
- Function attachments
- Shared calculation engine

### Phase 4: Ledger Modules
- Daily income
- Daily billing
- Vendor entry
- Admin income
- Shared totals and attachment patterns

### Phase 5: Reports and Exports
- Admin dashboard rollups
- Module reports
- Cross-module totals
- Service and package reporting
- Employee-wise and venue-wise reporting
- Excel exports

### Phase 6: Hardening
- QA regression pass
- Mobile polish
- Performance tuning
- Hosting-readiness review
- Optional audit/history

## Acceptance Matrix
- Employees cannot enter dashboards without selecting an assigned venue.
- Venue switching changes all employee-facing counts, tables, and totals.
- Function Entry supports Packages, Extra Charges, Installments, and Discounts.
- Function calculations always follow the agreed formulas.
- Frozen fund applies only to Employee Type A and only in function totals.
- Daily Income, Daily Billing, Vendor Entry, and Admin Income support multiple attachments and totals.
- Admin has full reporting and export capability across relevant filters.
- No currency symbol appears in UI or exports.
- UI remains polished and usable on mobile, tablet, and desktop.

## Non-Goals for Initial Implementation
- Public registration
- Multi-company tenancy
- Real-time websocket dashboards
- Queue-dependent export pipeline
- Employee self-service role changes
- Deep audit/history before core workflows stabilize

## Test Strategy
- Feature tests for auth, venue middleware, venue switching, and role protection.
- CRUD and authorization tests for every module.
- Calculation tests for function totals, frozen fund, and edge cases.
- Upload tests for MIME allowlist, size limits, and secure downloads.
- Report and export tests for filters, totals, and symbol-free output.
- Responsive smoke review for phone, tablet, and desktop.

## Subagent Operating Model
- Architecture subagent before schema, migrations, packages, or deployment-affecting changes.
- UI/UX subagent before shell, design system, complex forms, and Function Entry workflow decisions.
- Auth and permissions subagent before route, policy, venue, or frozen-fund logic changes.
- Reports and exports subagent before aggregate query or export design changes.
- QA/review subagent at the end of each phase and before merge.

## Recommended Next Implementation Step
- Start Phase 1 only after reviewing these guidance files.
- First coding prompt should ask for the Laravel foundation only: auth, fixed roles, venue selection flow, middleware, and the premium responsive app shell.
