---
name: auth-roles-venue-workflow
description: Define and review authentication, fixed roles, permissions, and venue-selection enforcement for this internal CRM. Use when Codex needs to design or change login flow, venue selection, middleware, route protection, employee management permissions, or frozen-fund eligibility rules.
---

# Auth Roles Venue Workflow

## Use This Skill To
- Keep the login and venue workflow consistent.
- Enforce the fixed role matrix exactly as defined by the project.
- Prevent route, policy, and session drift that could bypass venue selection or permissions.

## Fixed Role Model
- Use a fixed `UserRole` backed enum:
  - `admin`
  - `employee_a`
  - `employee_b`
  - `employee_c`
- Do not introduce a generic user-editable permissions builder in v1.

## Login and Venue Contract
- Use Laravel session authentication.
- Employee flow must be:
  - login
  - venue selection
  - role-based dashboard
- Store active venue only as `selected_venue_id` in session.
- Revalidate the selected venue against the employee's assigned venues on every request.
- If the selected venue is missing, inactive, or unassigned, clear it and redirect to venue selection.
- Admin may access the global admin dashboard without mandatory venue selection.

## Route and Policy Rules
- Protect app routes with auth middleware.
- Protect employee module routes with an `EnsureVenueSelected` middleware.
- Use policies, middleware, and shared access helpers instead of scattered inline role checks.
- Treat request-supplied user IDs and venue IDs as untrusted until validated.
- Default to fail-closed behavior.

## Employee Management Rules
- Admin can create, edit, deactivate, reset credentials for, and assign venues to employees.
- Admin controls role and employee type.
- Employees cannot change their own role, venue assignments, or core identity details.
- Keep self-service outside v1 unless later approved.

## Frozen Fund Rules
- Frozen fund applies only to `employee_a`.
- Frozen fund is assigned per employee plus venue.
- Frozen fund only affects function totals.
- Hide frozen-fund UI and access paths for `employee_b` and `employee_c`.

## Required Tests
- Redirect employees to venue selection after login.
- Deny employee dashboard access when venue is not selected.
- Reject switching to an unassigned venue.
- Clear venue session on logout.
- Enforce module access by role.
- Enforce admin-only employee management.
- Enforce frozen-fund visibility and logic only for `employee_a`.

## Review Checklist
- Does the change preserve `Login -> Venue Selection -> Dashboard` for employees?
- Does it keep roles fixed and centralized?
- Does it validate venue assignment before use?
- Does it keep employee management admin-only?
- Does it preserve frozen-fund eligibility boundaries?

## Output Style
- Produce concrete middleware, policy, route, and session recommendations.
- Flag any place where auth or venue context could be bypassed.
