# CRM_BRIEF.md

## Project Overview

This project is an **internal company CRM** for admin and employees only. It is **not** a public multi-company SaaS. The CRM must be built as a **Laravel application** with a **premium modern UI**, strong responsiveness for **mobile, tablet, and desktop**, and a clean structure that can be developed using **Codex with agents, skills, and subagents**.

The application will be developed locally in **VS Code**, with **XAMPP MySQL/MariaDB** running in the background for the database during development. The final project should remain compatible with **Hostinger Business hosting** for deployment.

The main priority is:
- best possible UI
- mobile responsive admin/dashboard design
- clean architecture
- accurate calculations
- role-based dashboards
- venue-based workflow
- future maintainability

Codex should decide the actual database schema, tables, migrations, models, and relationships based on this business brief. The goal of this file is to define the **full business functionality and rules**.

---

## Core Technical Direction

- Framework: Laravel
- Database: MySQL / MariaDB
- Local Dev: VS Code + XAMPP database
- Production Goal: Hostinger Business compatible
- UI Goal: premium, modern, responsive, polished admin dashboard style
- Currency Rule: **do not show any rupee sign, dollar sign, or currency symbol anywhere in the UI or exports**
- Codex should create the project structure, `AGENTS.md`, skills, phased build plan, and implementation based on this brief

---

## Main Users

There are 4 user categories in the CRM:

1. **Admin**
2. **Employee Type A**
3. **Employee Type B**
4. **Employee Type C**

This is an internal CRM only for company use and employees.

---

## Employee Types and Permissions

### Admin
Admin has full control over the CRM.

Admin can:
- create, edit, delete venues
- create and manage employees
- assign one or multiple venues to employees
- decide employee type
- manage employee access and credentials
- create and manage services
- create and manage packages
- assign services/packages based on venue and employee
- assign frozen fund for Type A employees per venue
- view all employee data across all modules
- access full reports and exports
- manage vendor names
- create Admin Income entries
- see totals by employee, venue, service, type, and date range

---

### Employee Type A
Employee Type A can access:
- venue selection
- dashboard
- function entry
- daily income
- daily billing

Special rules:
- no vendor entry
- has frozen fund feature in Function Entry only
- frozen fund is assigned by admin per employee per venue

---

### Employee Type B
Employee Type B can access:
- venue selection
- dashboard
- function entry
- daily income
- daily billing
- vendor entry

Special rules:
- no frozen fund
- vendor entry includes 4 vendors
- vendor names are editable by admin

---

### Employee Type C
Employee Type C can access:
- venue selection
- dashboard
- function entry only

Special rules:
- no daily income
- no daily billing
- no vendor entry
- no frozen fund

---

## Venue-Based Workflow

This CRM is venue-based.

Admin creates venues and assigns one or more venues to each employee.

After login:
1. user logs in
2. employee must choose one assigned venue
3. selected venue is stored in session
4. all dashboard values, listings, entries, filters, and totals must be based on the selected venue
5. employee can switch venue later
6. switching venue changes the full dashboard context and all related records/totals

### Login Flow
`Login -> Venue Selection -> Role-Based Dashboard`

Rules:
- employee cannot access working dashboard without selecting a venue
- selected venue must control all module data
- each venue should behave like its own context/dashboard

---

## Dashboard by User Type

### Employee Type A Dashboard
Main modules:
- Function Entry
- Daily Income
- Daily Billing

Dashboard should show:
- selected venue
- function totals
- daily income totals
- daily billing totals
- paid totals
- pending totals
- frozen fund total
- total after frozen fund
- recent entries

---

### Employee Type B Dashboard
Main modules:
- Function Entry
- Daily Income
- Daily Billing
- Vendor Entry

Dashboard should show:
- selected venue
- function totals
- daily income totals
- daily billing totals
- vendor totals
- paid totals
- pending totals
- recent entries

---

### Employee Type C Dashboard
Main modules:
- Function Entry only

Dashboard should show:
- selected venue
- function totals
- paid totals
- pending totals
- recent function entries

---

### Admin Dashboard
Admin dashboard should provide a high-level system overview with filters and totals.

Admin should be able to see:
- total venues
- total employees
- totals across all employees
- function entry totals
- daily income totals
- daily billing totals
- vendor totals
- admin income totals
- discounts totals
- extra charges totals
- installments totals
- service totals
- frozen fund totals
- employee-wise and venue-wise totals
- reports and export controls

UI should be highly polished and mobile responsive.

---

## Main Modules

The CRM should contain the following main modules:

1. Authentication
2. Venue Selection and Venue Switching
3. Employee Management
4. Venue Management
5. Service Management
6. Package Management
7. Function Entry
8. Daily Income
9. Daily Billing
10. Vendor Entry
11. Admin Income
12. Reports and Exports
13. Attachments / File Uploads
14. Totals and Calculation Engine
15. Audit / History if helpful

Codex should decide the exact Laravel architecture and database design.

---

## Daily Billing

Daily Billing is available to:
- Employee Type A
- Employee Type B

Each Daily Billing entry must allow:

- Date
- Name
- Amount
- Multiple File Uploads
- Notes

### File Upload Support
Uploads must support:
- images
- pdf
- doc/docx
- xls/xlsx
- csv
- camera files if possible through browser
- multiple file upload
- max 25 MB per file

### Daily Billing Rules
- employee can add entry
- employee can edit entry
- employee can delete entry
- employee can view uploaded files
- employee can download uploaded files
- total for a specific date must be shown
- overall grand total across all dates must be shown
- all data must stay filtered by selected venue

### Daily Billing Totals
- **Date Total** = total amount for that date
- **Grand Total** = all amounts combined across dates under current venue context

No currency symbol should appear anywhere.

---

## Daily Income

Daily Income is available to:
- Employee Type A
- Employee Type B

Daily Income is functionally the same as Daily Billing.

Each Daily Income entry must allow:
- Date
- Name
- Amount
- Multiple File Uploads
- Notes

### Daily Income Rules
- employee can add entry
- employee can edit entry
- employee can delete entry
- employee can view uploaded files
- employee can download uploaded files
- date total must be shown
- grand total must be shown
- all data must stay filtered by selected venue

No currency symbol should appear anywhere.

---

## Admin Income

Admin has a separate **Admin Income** module.

Admin Income should behave like Daily Income:
- Date
- Name
- Amount
- Multiple File Uploads
- Notes

This module is admin-only.

Admin should be able to:
- create
- edit
- delete
- view
- download attachments
- see totals

---

## Vendor Entry

Vendor Entry is available only to:
- Employee Type B

There are **4 vendors**.

Requirements:
- vendor names should be editable by admin
- each vendor has separate entries
- vendor entry behaves similarly to daily billing/income

Each Vendor Entry must allow:
- Date
- Name
- Amount
- Multiple File Uploads
- Notes

### Vendor Rules
- employee can add entry
- employee can edit entry
- employee can delete entry
- employee can view files
- employee can download files
- vendor-wise totals should be visible
- date-wise totals should be visible
- overall vendor totals should be visible
- all entries remain filtered by selected venue

---

## Function Entry

Function Entry is the most important module in the CRM.

It is available to:
- Employee Type A
- Employee Type B
- Employee Type C

### Base Function Entry Fields
When creating a Function Entry, the employee can add:
- Date
- Name
- Multiple File Uploads
- Notes

After a Function Entry is created, an **Action Center** becomes available for that entry.

The Action Center includes:
1. Packages
2. Extra Charges
3. Installments
4. Discounts

Each Function Entry must also show:
- total
- paid
- pending
- date-wise totals
- grand totals

---

## Function Entry - Packages

Admin creates packages and services.

Admin assigns packages/services based on:
- venue
- employee

Each package contains multiple services.

Each service has an admin-assigned rate.

When employee uses Packages inside Function Entry, the UI should allow service selection and line item calculations.

### Package / Service Row Structure
Each service row should support:
- Sr No
- Selection Mark
- Item Name
- Persons
- Rate
- Extra Charge
- Notes
- Total

### Rules
- service name and rate are pre-defined by admin
- employee fills or selects item details where applicable
- employee enters persons
- employee may add extra charge and notes
- line total must auto-calculate

### Line Total Formula
`Line Total = (Persons × Rate) + Extra Charge`

### Selection Logic
Employees can select rows/services using selection mark.

### Package Totals
- selected line totals under one service/modal should calculate a service total
- multiple service totals should combine into package total
- package total should contribute to Function Entry total

### Reporting Need
Admin must later be able to see:
- service total per employee
- service total per venue
- combined service totals across employees
- service totals in reports and exports

---

## Function Entry - Extra Charges

Each Function Entry has an Extra Charges action.

Extra Charges modal should allow multiple entries with:
- Date
- Name
- Mode
- Amount
- Note
- Multiple File Uploads

### Modes
Supported modes:
- Cash
- UPI
- Bank
- Card
- Other
- Sweets

### Rules
- multiple extra charges can be added to a function entry
- each extra charge should be stored separately
- extra charge amount increases the function total
- attachments must be viewable/downloadable

### Effect on Total
Extra Charges add to Function Entry total.

---

## Function Entry - Installments

Each Function Entry has an Installments action.

Installments modal should allow multiple entries with:
- Date
- Name
- Mode
- Amount
- Note
- Multiple File Uploads

### Modes
Supported modes:
- Cash
- UPI
- Bank
- Card
- Other
- Sweets

### Rules
- multiple installments can be added
- installment entries should be stored individually
- installments affect paid and pending values
- attachments must be viewable/downloadable

### Installment Logic
Installments should not reduce the original function value. They should reduce the pending amount.

Use this business logic:
- **Paid = sum of installments**
- **Pending = Final Function Total - Paid**

---

## Function Entry - Discounts

Each Function Entry has a Discounts action.

Discounts modal should allow multiple entries with:
- Date
- Name
- Mode
- Amount
- Note
- Multiple File Uploads

### Modes
Supported modes:
- Cash
- UPI
- Bank
- Card
- Other
- Sweets

### Rules
- multiple discounts can be added
- discount entries should be stored individually
- discount reduces function total
- attachments must be viewable/downloadable

### Discount Logic
Discount amount reduces the Function Entry total.

---

## Function Entry - Final Calculation Logic

Codex should implement Function Entry totals using correct business logic.

Recommended business logic:

### Function Total
`Function Total = Package Total + Extra Charges - Discounts`

### Paid
`Paid = Sum of Installments`

### Pending
`Pending = Function Total - Paid`

This is the preferred financial structure.

All date-wise totals and grand totals must be based on these values.

---

## Frozen Fund

Frozen Fund applies only to:
- Employee Type A
- Function Entry only
- specific venue
- assigned by admin

### Rules
- admin assigns frozen fund per employee and per venue
- frozen fund is not for Type B
- frozen fund is not for Type C
- frozen fund should be reflected in totals clearly

### Display Logic
Show:
- Function Grand Total
- Frozen Fund
- Grand Total After Frozen Fund

### Formula
`Net Total After Frozen Fund = Function Grand Total - Frozen Fund`

This should be shown clearly on Type A dashboard and relevant reports.

---

## Date Totals and Grand Totals

The CRM must show clear totals throughout modules.

### Daily Billing
- date total
- grand total

### Daily Income
- date total
- grand total

### Vendor Entry
- vendor-wise total
- date total
- grand total

### Function Entry
- per entry total
- per entry paid
- per entry pending
- date-wise total
- overall grand total
- frozen fund adjusted totals where applicable

---

## Services and Packages Management by Admin

Admin must be able to manage:
- services
- service rates
- packages
- package-service mapping
- venue-wise service availability
- employee-wise service/package assignment

Admin should be able to define which employee and which venue can use which services/packages.

Codex should design the best backend structure for this.

---

## Venue Totals and Admin Analysis

Admin needs strong visibility across venue and employee performance.

Admin must be able to view:
- totals by employee
- totals by venue
- totals by service
- totals by employee type
- combined totals for employee 1 + employee 2 etc.
- grand total for a specific service across employees
- function totals by venue and employee
- package totals
- service totals
- daily billing totals
- daily income totals
- vendor totals
- admin income totals

This should later be accessible through reports/dashboard widgets.

---

## Reports and Export

Reports and exports are admin-only.

All exports and reports must support filters:
- employee
- venue
- employee type
- date range
- module where relevant

Exports should be available in Excel format.

### Report Areas
Admin should be able to export:
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

### Function Reports Must Include
- function base entries
- package totals
- service totals
- extra charges
- discounts
- installments
- paid
- pending
- frozen fund where applicable
- net total after frozen fund

### Export Rules
- exports must be clean and readable
- no currency symbol anywhere
- include totals rows where useful
- remain filtered by selected criteria

---

## Attachments / File Handling

Many modules use attachments.

Supported modules include:
- Function Entry
- Extra Charges
- Installments
- Discounts
- Daily Billing
- Daily Income
- Vendor Entry
- Admin Income

### Attachment Requirements
- multiple file upload
- support images, pdf, doc/docx, xls/xlsx, csv
- max 25 MB per file
- view where possible
- download allowed
- file access should remain linked to the correct record
- file handling should be secure and organized

Codex should design the best implementation pattern.

---

## Employee Management

Admin fully controls employee management.

Admin should be able to:
- create employee
- edit employee
- control employee type
- assign one or multiple venues
- manage credentials
- activate/deactivate employee
- control names and details

Employees should **not** be allowed to change their own role/name/core identity details unless explicitly planned later.

---

## Vendor Management

Admin should be able to manage vendor names for Type B employees.

Requirements:
- 4 vendors
- names editable by admin
- vendor entries linked properly in reporting

Codex should decide whether vendors are global, venue-based, or employee-based according to best structure, but functionality must satisfy the business need.

---

## UI / UX Expectations

This project must have a **best-in-class dashboard UI** for its stack.

### UI Goals
- premium look
- modern admin design
- smooth spacing
- responsive on mobile, tablet, desktop
- easy navigation
- clear totals
- clean forms
- strong usability for employees

### Layout Preference
- left sidebar
- top navigation/header
- venue switcher visible
- cards for totals
- data tables for records
- filters above listings
- clean modals/drawers for actions
- polished dashboard experience
- strong mobile usability

### Function Entry UI
Because Function Entry is complex, its interface should be carefully designed for usability:
- clear row actions
- well-designed action center
- packages / charges / installments / discounts handled cleanly
- no cluttered interface
- mobile-friendly interaction

Codex should prioritize high-quality UI design and component structure.

---

## Performance and Hosting Considerations

The application should be built with future deployment to **Hostinger Business** in mind.

This means:
- Laravel structure should remain hosting-friendly
- avoid overengineering that breaks shared hosting compatibility
- use practical packages
- keep uploads manageable
- support file manager style deployment if needed
- production `.env` should be straightforward

The project is being developed locally in VS Code with XAMPP database, but final deployment should remain realistic for Hostinger Business.

---

## AI Build Expectations

Codex is expected to:
- read this brief fully
- create `AGENTS.md`
- create `PROJECT_PLAN.md`
- create skill folders and `SKILL.md` files
- create a phased build order
- use subagents deliberately
- decide database schema and migrations from the functionality
- choose clean Laravel architecture
- prioritize UI quality and responsiveness
- avoid unnecessary questions if a reasonable decision can be made
- build in phases instead of trying everything at once

---

## Expected Subagent Areas

Codex should explicitly use subagents for specialized planning and implementation.

Suggested subagent responsibilities:
- architecture and Laravel structure
- auth, roles, and venue selection flow
- UI/UX and responsive dashboard system
- function entry logic and calculations
- reports and exports
- QA/review and consistency checks

---

## Acceptance Criteria

The project should satisfy these core requirements:

1. Employees must select a venue before accessing any dashboard.
2. Each venue shows its own data; switching venue changes all counts and totals.
3. Function Entry must support Packages, Extra Charges, Installments, and Discounts.
4. Packages modal must calculate totals correctly.
5. Extra charges must increase Function Entry total.
6. Discounts must reduce Function Entry total.
7. Installments must affect paid and pending correctly.
8. Frozen Fund applies only to Employee Type A and only in Function Entry.
9. Attachments up to 25 MB must be uploadable, viewable where possible, and downloadable.
10. Admin must be able to see all employee entries across all relevant modules.
11. Admin must be able to export reports by employee + venue + date range.
12. Admin must be able to view service totals by employee and combined across employees.
13. No currency symbol should appear anywhere in the UI or exports.
14. UI must be highly polished and mobile responsive.

---

## Final Instruction to Codex

Use this brief as the source of truth for business functionality.

Your responsibilities:
- convert this brief into proper repo guidance files
- create the best planning structure
- define project architecture
- decide database design from functionality
- build in safe phases
- prioritize clean Laravel standards
- prioritize premium responsive UI
- keep compatibility with Hostinger Business in mind

Do not reduce or oversimplify the business rules. The CRM must reflect this functionality accurately.