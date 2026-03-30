---
name: premium-responsive-ui
description: Design and review the premium responsive UI system for this Laravel CRM. Use when Codex needs to plan or implement the app shell, dashboards, responsive tables, forms, modals, drawers, venue switcher visibility, or the complex Function Entry interaction flow across mobile, tablet, and desktop.
---

# Premium Responsive UI

## Use This Skill To
- Keep the CRM visually premium, modern, and highly responsive.
- Standardize dashboard, form, table, and modal behavior.
- Design complex workflows, especially Function Entry, as staged and usable interactions.

## Use This UI Stack
- Blade components for layout and reusable UI parts.
- Livewire for interactive screens and filters.
- Alpine.js for lightweight UI state.
- Tailwind CSS for tokens and component styling.

## Preserve These Layout Rules
- Use a premium app shell with:
  - left sidebar on desktop
  - sticky top header
  - visible venue switcher
  - responsive KPI cards
  - filters above listings
  - drawer or modal patterns for focused tasks
- Keep the current venue visible on every employee-facing page after selection.
- Design mobile behavior intentionally; never leave tables or forms to degrade by accident.

## Dashboard Guidance
- Admin dashboard: dense KPI cards, report shortcuts, cross-venue filters, recent activity.
- Employee A dashboard: function totals, daily income, daily billing, frozen fund, recent entries.
- Employee B dashboard: function totals, daily income, daily billing, vendor totals, recent entries.
- Employee C dashboard: function totals, paid, pending, recent function entries.
- Differentiate dashboards by emphasis, not by unrelated duplicated layout systems.

## Function Entry Guidance
- Treat Function Entry as the highest-complexity workflow.
- Use a staged flow:
  - create base function entry
  - open an action center
  - separate Packages, Extra Charges, Installments, and Discounts into tabs, segmented controls, or slide-over panels
- Keep summary totals visible while editing.
- Use clean inline or row-based entry for service lines.
- Keep attachments in a dedicated drop zone or upload area with visible file state.
- Avoid crowding all actions into one long form.

## Responsive Rules
- Define phone, tablet, and desktop behavior for every new screen.
- Collapse dense tables into stacked or card-like presentations on smaller screens when needed.
- Keep touch targets comfortable and form controls readable on mobile.
- Maintain visible focus states, keyboard navigation, and accessible dialog behavior.

## Visual Rules
- Use a restrained premium palette and consistent spacing scale.
- Use consistent radii, shadows, borders, and elevation.
- Keep totals prominent and readable.
- Never show currency symbols; totals must render as plain numeric values with clear labels.

## UI Review Checklist
- Is venue context visible?
- Is the screen usable on phone, tablet, and desktop?
- Are the primary actions obvious without clutter?
- Are totals readable and consistent?
- Does the screen reuse shared components instead of bespoke markup?
- Does the interaction flow reduce mistakes for employees working quickly?

## Output Style
- Produce concrete UI structure recommendations, component lists, or workflow patterns.
- Prefer server-first Laravel UI patterns unless there is a strong reason to escalate complexity.
