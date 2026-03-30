---
name: architecture
description: Define and preserve the Laravel architecture for this internal CRM. Use when Codex needs to make or review decisions about stack choice, modular boundaries, schema direction, migrations, attachment strategy, financial data handling, package selection, or Hostinger Business deployment compatibility.
---

# Architecture

## Use This Skill To
- Lock implementation decisions to the approved Laravel modular-monolith architecture.
- Prevent schema drift from the CRM business brief.
- Keep package choices compatible with Hostinger Business shared hosting.
- Preserve server-side calculation authority and venue-scoped data rules.

## Apply These Defaults
- Use Laravel with Blade, Livewire 3, Alpine.js, Tailwind CSS, and Vite.
- Keep the app as a modular monolith, not a SPA and not split services.
- Organize code by bounded context:
  - `Access`
  - `MasterData`
  - `Functions`
  - `Ledgers`
  - `Reports`
  - `Files`
  - `Shared`
- Keep controllers thin and move business logic into actions, services, policies, form requests, and query objects.

## Enforce Non-Negotiable Constraints
- Keep deployment compatible with Hostinger Business.
- Do not depend on Redis, Horizon, websockets, SSR, or always-on workers.
- Do not use floats for financial data.
- Do not show currency symbols or currency codes anywhere.
- Keep server-side calculations authoritative.
- Keep employee-facing data scoped by selected venue.

## Use This Data Direction
- Use fixed internal roles on `users`.
- Use a many-to-many venue assignment pivot with frozen fund amount stored per employee plus venue assignment.
- Use master data tables for venues, services, packages, package-service mapping, and venue vendors.
- Use transaction tables for function entries, function child records, daily income, daily billing, vendor entries, admin income, and polymorphic attachments.
- Use exact integer minor units for every stored amount.

## Apply These Calculation Rules
- `Line Total = (Persons x Rate) + Extra Charge`
- `Function Total = Package Total + Extra Charges - Discounts`
- `Paid = Sum of Installments`
- `Pending = Function Total - Paid`
- `Net Total After Frozen Fund = Function Grand Total - Frozen Fund`
- Recalculate and persist function snapshots transactionally after child-record writes.

## Package Decision Rules
- Prefer Laravel core features first.
- Allow `maatwebsite/excel` for `.xlsx` exports.
- Treat any new third-party package as requiring a Hostinger compatibility check, maintenance check, and value justification.
- Defer audit/history packages until core workflows stabilize.

## Architecture Review Checklist
- Does the change preserve venue-based isolation?
- Does it keep money handling exact and symbol-free?
- Does it place business logic outside controllers and views?
- Does it stay compatible with shared hosting?
- Does it reuse the approved bounded contexts instead of introducing scattered patterns?

## Build Order Dependency Map
- Foundation before master data.
- Master data before Function Entry.
- Function Entry and ledger modules before reports.
- Reports before performance tuning and audit/history.

## Output Style
- Produce an architecture decision memo or implementation recommendation.
- Call out any conflict with `CRM_BRIEF.md`, `PROJECT_PLAN.md`, or `AGENTS.md`.
- Prefer concrete, hosting-safe Laravel patterns over abstract redesigns.
