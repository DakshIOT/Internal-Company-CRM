---
name: qa-review
description: Review this CRM for business-rule correctness, regression risk, and release readiness. Use when Codex needs to review code or plans for venue isolation, permissions, calculations, uploads, responsive UI, reporting, exports, or phase-gate acceptance before merge.
---

# QA Review

## Use This Skill To
- Review changes against business rules, not only code style.
- Catch venue leakage, permission drift, calculation errors, upload issues, export mistakes, and mobile regressions.
- Gate each phase before merge.

## Primary Review Areas
- Venue selection enforcement
- Role-based module access
- Function Entry calculations
- Frozen fund eligibility
- Attachment validation and access control
- Report and export correctness
- Responsive behavior on phone, tablet, and desktop
- Hostinger compatibility

## Mandatory Findings Mindset
- Assume the bug is in scope, query logic, permission logic, or calculation logic until proven otherwise.
- Report findings in severity order with business impact.
- Prefer business-outcome failures over stylistic commentary.

## Required Regression Checks
- No employee dashboard access without selected venue.
- No cross-venue leakage in tables, counts, totals, or reports.
- No unauthorized role access to hidden modules or actions.
- No incorrect function math for totals, paid, pending, discounts, extra charges, installments, or frozen fund.
- No attachment access through raw or unguarded URLs.
- No currency symbols in UI, exports, filenames, fixtures, or tests.
- No mobile layout breakage in shell, tables, forms, or Function Entry action center.

## Phase Gate Coverage
- Phase 1: auth, role access, venue selection, venue switching.
- Phase 2: master-data CRUD and assignment rules.
- Phase 3: Function Entry workflow, child actions, calculations, and attachments.
- Phase 4: ledger module CRUD, totals, and attachments.
- Phase 5: reports, exports, filters, totals alignment, no-symbol output.
- Phase 6: end-to-end regression, mobile polish, deployment readiness.

## Acceptance Checklist
- Does the change match `CRM_BRIEF.md` and `PROJECT_PLAN.md`?
- Are permission and venue scopes explicit and tested?
- Are financial formulas correct and covered by tests?
- Are uploads validated and downloads authorized?
- Are exports correct, readable, and symbol-free?
- Is the UI still usable on small screens?
- Does the implementation remain shared-hosting safe?

## Output Style
- Present findings first.
- Use clear severity ordering.
- Include concise file references when reviewing code.
- Mention residual risk or missing tests if no findings are present.
