---
name: reports-exports
description: Plan and review reporting, aggregation, and Excel exports for this CRM. Use when Codex needs to design or change admin reports, shared report filters, totals queries, workbook exports, service or package rollups, or no-currency-symbol output rules.
---

# Reports Exports

## Use This Skill To
- Keep admin reporting consistent across modules.
- Standardize shared filters, totals logic, and export patterns.
- Prevent reporting drift between UI totals and exported totals.

## Reporting Contract
- Reports and exports are admin-only.
- Reuse one shared filter contract across report families:
  - venue
  - employee
  - employee type
  - module
  - date range
  - vendor where relevant
  - service where relevant
  - package where relevant
- Use explicit admin filters instead of employee venue-session assumptions.

## Query Rules
- Prefer SQL aggregates and indexed queries over in-memory collection math.
- Normalize reportable records around `venue_id`, `employee_id`, `employee_type`, and `entry_date`.
- Keep function parent records updated with snapshot totals so reporting does not need to rebuild every total from scratch for common cases.
- Use drilldown queries only when the report needs record detail.

## Export Rules
- Use `maatwebsite/excel` for `.xlsx` output.
- Keep exports synchronous or chunked without requiring long-running workers.
- Include totals rows or summary sheets where useful.
- Use stable headings and predictable column order.
- Clean up temporary files if export generation uses local temporary storage.

## No-Currency-Symbol Rule
- Render all amounts as plain numeric values only.
- Do not use currency symbols, currency codes, or currency-formatted Excel cells.
- Check headings, cells, filenames, and sheet names for currency leakage.

## Function Reporting Rules
- Keep these formulas identical in UI, queries, and exports:
  - `Function Total = Package Total + Extra Charges - Discounts`
  - `Paid = Sum of Installments`
  - `Pending = Function Total - Paid`
  - `Net Total After Frozen Fund = Function Grand Total - Frozen Fund`

## Report Families
- Function Entry reports
- Daily Billing reports
- Daily Income reports
- Vendor reports
- Admin Income reports
- Service totals
- Package totals
- Employee-wise totals
- Venue-wise totals
- Date-range totals

## Review Checklist
- Do active filters match the query?
- Do totals come from database aggregation where practical?
- Does the export match the on-screen totals?
- Are all outputs free of currency symbols?
- Is the report admin-only and permission-safe?

## Output Style
- Produce concrete query, filter, workbook, and validation recommendations.
- Call out performance risks and filter-scope mistakes early.
