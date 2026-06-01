# PettyCash Web App Flow

Purpose: this file describes the actual PettyCash web module flow as it exists today, from login through day-to-day operations, admin role management, spendings, token hostels, maintenance, ledger, reports, and notifications.

This is meant to be the reference for matching the mobile app to the web app.

## How To Read This File

There are two ways to use this document:

1. The first part maps the module structure, routes, permissions, and business rules.
2. The later part gives the actual user journey page by page:
   - where the user lands
   - what information the page shows
   - what actions are available
   - what page comes next
   - what feedback the system gives

If you are checking whether the mobile app matches the web app experience, the most important part is the page-by-page journey section near the end.

## 1. Entry and Login

Base entry points:

- `GET /petty/login` -> `petty.login`
- `POST /petty/login` -> `petty.login.submit`
- `POST /petty/logout` -> `petty.logout`
- Main app entry after auth: `GET /petty/` -> `petty.dashboard`

Login screen behavior:

- Simple login form.
- Fields:
  - `email`
  - `password`
  - `remember`
- User signs in first, then lands in the authenticated PettyCash module.

## 2. Roles and Permission Model

The web module is permission-driven through `PettyAccess`.

Core roles:

- `admin`
- `finance`
- `customer_care`
- `viewer`

High-level permission buckets:

- dashboard
- reports
- ledger
- credits
- batches
- bikes
- meals
- meals_daily
- tokens
- others
- bikes_master
- respondents
- maintenances
- notifications
- profile
- settings

How access behaves:

- `admin` has full access and can manage users, roles, and explicit permissions.
- `finance` typically has broad operational access across credits, spendings, reports, and ledger.
- `customer_care` and `viewer` are limited by assigned/default permissions.
- Navigation visibility is not hardcoded by role labels alone. It is shown or hidden based on permission checks.

Important implication for mobile:

- The web app is not just one dashboard shown to everyone.
- What the user sees depends on role plus explicit permission assignments.

## 3. Main Navigation Structure

The left navigation is grouped roughly like this:

### Overview

- Dashboard
- Reports
- Ledger

### Money In

- Credits
- Batches

### Spendings

- Motor Vehicles
- Meals
- Meal Bills / Daily Meals
- Token Hostels
- Other Spendings

### Operations / Master Data

- Bikes / Vehicles Master
- Respondents
- Maintenance

### Account / Admin

- Notifications
- Profile
- Settings

The mobile app should mirror this structure functionally even if the layout changes.

## 4. Login to First Working Screen

Typical flow after login:

1. User logs in.
2. User lands on Dashboard.
3. From Dashboard they branch into:
   - money in
   - spendings
   - maintenance
   - ledger
   - reports
   - notifications
   - settings/profile

## 5. Dashboard / Overview Flow

Route:

- `GET /petty/` -> `petty.dashboard`

Purpose:

- Executive overview of the current petty cash state.
- Date-filtered summary view.

Dashboard data behavior:

- Uses date filters `from` and `to`.
- Pulls dashboard summary from the balance service.
- Includes bike service spend in totals.
- Can show:
  - total credited
  - total spent
  - balance
  - category breakdown
  - bike maintenance urgency widgets

Dashboard permission granularity:

- `dashboard.summary`
- `dashboard.category_totals`
- `dashboard.breakdown`

Expected web-style flow:

1. User lands on dashboard.
2. User may change `from` and `to`.
3. User reads totals and breakdown.
4. User drills into the next working area:
   - Credits
   - Batches
   - Spendings
   - Maintenance
   - Ledger
   - Reports

## 6. Money In Flow

### 6.1 Credits

Routes:

- `GET /petty/credits` -> list credits
- `GET /petty/credits/create` -> create credit
- `POST /petty/credits` -> save credit
- `GET /petty/credits/{credit}/edit` -> edit credit
- `PUT /petty/credits/{credit}` -> update credit
- `DELETE /petty/credits/{credit}` -> delete credit
- `GET /petty/credits/pdf` -> export credits

Credit list behavior:

- Filters:
  - `from`
  - `to`
  - `q`
  - `sort`
- Export supported:
  - PDF
  - CSV
  - Excel

Credit creation flow:

The web create screen is effectively a 2-step workflow:

1. Credit basics
   - `reference`
   - `amount`
   - `date`
2. Charges and note
   - `transaction_cost`
   - `description`

Important system behavior:

- Saving a credit also creates a new funding batch.
- After save, the web flow redirects into the batch detail flow.

### 6.2 Batches

Routes:

- `GET /petty/batches` -> list batches
- `GET /petty/batches/{id}` -> batch detail

Batch role in the system:

- Credits create batches.
- Spendings allocate from available batch balances.
- Allocation can be automatic across batches or forced to one selected batch depending on module/form.

## 7. Spendings Flow

Spendings are split into separate web modules, not one generic form.

Main spending families:

- Bike spendings
- Meals
- Meal daily bills/payments
- Token hostels and token payments
- Other spendings

## 8. Bike Spending Flow

Routes:

- `GET /petty/spendings/bikes`
- `GET /petty/spendings/bikes/create`
- `POST /petty/spendings/bikes`
- `GET /petty/spendings/bikes/{spending}/edit`
- `PUT /petty/spendings/bikes/{spending}`
- `DELETE /petty/spendings/bikes/{spending}`
- `GET /petty/spendings/bikes/pdf`
- `GET /petty/bikes/{bike}/spendings` -> drilldown by bike

List behavior:

- Type is fixed to `bike`.
- Filters:
  - `from`
  - `to`
  - `sub_type`
  - `batch_id`
  - `q`
  - `sort`
- Search can match:
  - reference
  - description
  - particulars
  - bike plate/model
  - respondent name/phone
  - batch number

Create flow:

The web bike spending form is a real multi-step workflow:

1. Funding and bike
   - `funding` = `auto` or `single`
   - `batch_id` when single batch mode is used
   - `sub_type` = `fuel` or `maintenance`
   - `bike_id`
2. Payment details
   - `reference`
   - `amount`
   - `transaction_cost`
   - `date`
3. Respondent and note
   - `respondent_id`
   - `description`
   - `particulars`

Important rules:

- Only active/selectable bikes can be used for new spending.
- Only active/selectable respondents can be used for new spending.
- If `sub_type = maintenance`, `particulars` is required.
- Funding can be:
  - automatic smallest-first allocation across available batches
  - single selected batch allocation

What this means for mobile:

- Bike spending is not just type + amount.
- The web flow enforces active bike filtering and maintenance-specific details.

## 9. Meal Spending Flow

Routes:

- `GET /petty/spendings/meals`
- `GET /petty/spendings/meals/create`
- `POST /petty/spendings/meals`
- `GET /petty/spendings/meals/{spending}/edit`
- `PUT /petty/spendings/meals/{spending}`
- `DELETE /petty/spendings/meals/{spending}`
- `GET /petty/spendings/meals/pdf`

List behavior:

- Uses `type = meal`
- Includes `sub_type in [lunch, daily_payment]`
- Filters:
  - `from`
  - `to`
  - `batch_id`
  - `q`
  - `sort`

Create flow:

The normal meals page supports both one-off and mass lunch disbursement.

Form behavior:

1. Funding source
   - `funding`
   - `batch_id`
2. Payment schedule choice
   - one date or date range
   - `mass` mode allowed
   - `date`
   - `range_from`
   - `range_to`
3. Payment details
   - `reference`
   - `amount`
   - `transaction_cost`
   - `description`

Important rules:

- Auto funding checks overall available balance before save.
- Mass mode creates multiple spending rows, one per date.

## 10. Meal Daily Bills and Payments Flow

Routes:

- `GET /petty/spendings/meals/daily`
- `GET /petty/spendings/meals/daily/calculate`
- `POST /petty/spendings/meals/daily`
- `GET /petty/spendings/meals/daily/{dailySpending}/edit`
- `PUT /petty/spendings/meals/daily/{dailySpending}`
- `DELETE /petty/spendings/meals/daily/{dailySpending}`
- `POST /petty/spendings/meals/daily/payments`
- `GET /petty/spendings/meals/daily/payments/{payment}/edit`
- `PUT /petty/spendings/meals/daily/payments/{payment}`
- `DELETE /petty/spendings/meals/daily/payments/{payment}`

Purpose:

- This is a separate workflow from ordinary meal spending.
- It supports preparing daily meal bills first, then posting payments against them.

Important behavior:

- Multiple day entries can be prepared.
- Respondents are attached to daily entries.
- Paid daily bills cannot be edited directly; the payment must be edited instead.

This is a distinct operational feature and should not be collapsed carelessly in mobile.

## 11. Other Spending Flow

Routes:

- `GET /petty/spendings/others`
- `GET /petty/spendings/others/create`
- `POST /petty/spendings/others`
- `GET /petty/spendings/others/{spending}/edit`
- `PUT /petty/spendings/others/{spending}`
- `DELETE /petty/spendings/others/{spending}`
- `GET /petty/spendings/others/pdf`

List filters:

- `from`
- `to`
- `batch_id`
- `q`
- `sort`

Create form fields:

- `funding`
- `batch_id`
- `reference`
- `amount`
- `transaction_cost`
- `date`
- `respondent_id`
- `description`

Important rule:

- If respondent is supplied, the respondent must be active/selectable.

## 12. Token Hostels and Token Payments Flow

This is the most specialized area in the web module.

Main routes:

- `GET /petty/spendings/tokens` -> hostel list / due management
- `GET /petty/spendings/tokens/create` -> create hostel
- `POST /petty/spendings/tokens/hostels` -> save hostel
- `GET /petty/spendings/tokens/hostels/{hostel}` -> hostel detail
- `PUT /petty/spendings/tokens/hostels/{hostel}` -> update hostel
- `GET /petty/spendings/tokens/hostels/{hostel}/agreement`
- `PUT /petty/spendings/tokens/hostels/{hostel}/agreement`
- `POST /petty/spendings/tokens/hostels/{hostel}/agreement/terminate`
- `POST /petty/spendings/tokens/hostels/{hostel}/payments` -> record token payment
- `GET /petty/spendings/tokens/payments/{payment}/edit`
- `PUT /petty/spendings/tokens/payments/{payment}`
- `DELETE /petty/spendings/tokens/payments/{payment}`
- `GET /petty/spendings/tokens/pdf`
- `GET /petty/spendings/tokens/hostels/{hostel}/pdf`

### 12.1 Hostel list / token operations

The token hostels index is not just a simple list.

It supports:

- searching hostels
- due sorting
- due filtering:
  - overdue
  - due today
  - due tomorrow
  - due in 2 days
  - due in 3 days
  - no payments
  - terminated
- agreement-family behavior
- chained-hostel behavior
- reminders
- last payment visibility
- router totals

### 12.2 Hostel creation flow

The web hostel create screen is a structured workflow, not a minimal CRUD form.

Step 1 of 2 behavior:

- create hostel from one of two sources:
  - `ont_site`
  - `chained_hostel` if migrations/features support it

Fields and logic shown in create:

- `create_mode`
- `ont_key` for ONT/site-based hostels
- `hostel_name`
- `chained_from_hostel_id`
- `chained_from_ont_key`
- `contact_person`
- `phone_no`
- `no_of_routers`

Then the flow continues to agreement setup.

Important implication:

- Web hostel creation is tightly linked to ONT/site data and agreement setup.
- A very thin mobile hostel form will not match the web module.

### 12.3 Hostel agreement flow

After hostel creation, user continues to agreement handling:

- agreement view/update
- agreement termination
- agreement history suggestions
- parent/child family behavior for merged or chained setups

### 12.4 Token payment recording flow

Token payment is recorded from the hostel context, not as a generic top-level credit.

Typical payment fields include:

- `funding`
- `batch_id`
- `reference`
- `amount`
- `transaction_cost`
- `date`
- `receiver_name`
- `receiver_phone`
- `notes`
- `meter_no`

Additional behaviors in token module:

- helper payment sync
- overpay apply
- pending credits
- child hostel detach
- hostel QR generate/download
- ONT merge / refresh site serial

### 12.5 Token gateway request subsystem

Separate but related routes:

- `/spendings/tokens/gateway`
- `/spendings/tokens/gateway/create`
- store/update devices
- activate device
- test SMS
- quick pay from hostel
- send / refresh-check / confirm / retry / cancel / manual-link

This is a serious operational area, not just a basic spending form.

## 13. Master Data Flow

### 13.1 Bikes / Vehicles Master

Routes:

- `GET /petty/bikes-master`
- `GET /petty/bikes-master/create`
- `POST /petty/bikes-master`
- `GET /petty/bikes-master/{bike}/edit`
- `PUT /petty/bikes-master/{bike}`
- `DELETE /petty/bikes-master/{bike}`

Create flow:

1. Plate / registration
   - `plate_no`
   - `model`
2. Status
   - active/inactive style state

Important operational link:

- Only active bikes can be selected for new bike spendings.

### 13.2 Respondents

Routes:

- `GET /petty/respondents`
- `GET /petty/respondents/create`
- `POST /petty/respondents`
- `GET /petty/respondents/{respondent}`
- `GET /petty/respondents/{respondent}/edit`
- `PUT /petty/respondents/{respondent}`
- `DELETE /petty/respondents/{respondent}`
- `POST /petty/respondents/{respondent}/card/generate`
- `POST /petty/respondents/{respondent}/card/sms`

Create flow:

1. Basic identity
   - `name`
2. Extra details
   - `phone`
   - `staff_id`
   - `status`
   - `category`

Operational link:

- Only selectable respondents can be attached to new spendings.

## 14. Maintenance Flow

Routes:

- `GET /petty/maintenances`
- `GET /petty/maintenances/create`
- `POST /petty/maintenances`
- `GET /petty/maintenances/{bike}`
- `GET /petty/maintenances/services/{service}/edit`
- `PUT /petty/maintenances/services/{service}`
- `DELETE /petty/maintenances/services/{service}`
- mark/unmark unroadworthy routes

Main maintenance tabs:

- `schedule`
- `history`
- `unroadworthy`

Service record fields:

- `service_date`
- `next_due_date`
- `reference`
- `work_done`
- `amount`
- `transaction_cost`

Extra behavior:

- Bikes can be marked unroadworthy.
- Dashboard highlights due, overdue, never serviced, and unroadworthy units.

## 15. Ledger Flow

Routes:

- `GET /petty/ledger/spendings`
- `GET /petty/ledger/spendings/pdf`

Purpose:

- Unified ledger across credits and spendings.
- This is the strongest reporting/statement-style view in the module.

Filters:

- `from`
- `to`
- `batch_id`
- `type`
- `q`
- `calc`
- `export`

Ledger behavior:

- Unified row set
- search by reference and operational identifiers
- exports:
  - PDF
  - CSV
  - Excel
- includes fields such as:
  - amount
  - fee
  - total
  - source
  - reference
  - category / subtype
  - plate
  - meter

This is where users typically want full record history and export-friendly statements.

## 16. Reports Flow

Routes:

- `GET /petty/reports`
- `GET /petty/reports/bike`
- `GET /petty/reports/bike/pdf`
- `GET /petty/reports/respondent`
- `GET /petty/reports/respondent/pdf`
- `GET /petty/reports/batch`
- `GET /petty/reports/batch/pdf`
- `GET /petty/reports/general`
- `GET /petty/reports/general/pdf`

Reports hub behavior:

- report hub page
- quick filters:
  - `from`
  - `to`
  - `batch_id`
- quick exports for:
  - credits
  - bike spendings
  - meals
  - token hostels
  - others
  - token hostel payments by selected hostel

Main report types:

- Bike report
- Respondent report
- Batch report
- General / board report

General report behavior:

- accepts filters like:
  - `batch_ids`
  - `from`
  - `to`
  - `bike_id`
  - `respondent_id`
  - `include`
  - `view`
  - `format`
- combines credits and spending information
- produces gross / fee / net style outputs

## 17. Notifications Flow

Routes:

- `GET /petty/notifications`
- `POST /petty/notifications/mark-all-read`
- `POST /petty/notifications/{notification}/read`
- `POST /petty/notifications/admin-contacts`
- `DELETE /petty/notifications/admin-contacts/{contact}`
- `POST /petty/notifications/sms-templates`
- `DELETE /petty/notifications/sms-templates/{template}`
- `POST /petty/notifications/sms-settings`
- `POST /petty/notifications/auto-check`

This page is not just a notification list.

It also acts as a notification management center:

- unread count
- feed of notifications
- read/unread handling
- mark all as read
- SMS settings
- admin contact management
- SMS template management
- template usage tracking
- recipient usage tracking
- auto-check for shortfall / due-state scenarios

Operational meaning:

- The web notifications module mixes monitoring plus notification configuration.

## 18. Profile Flow

Routes:

- `GET /petty/profile`
- `POST /petty/profile/users/{user}/send-login-sms`

Purpose:

- Current user profile
- active sessions view
- admin-only system-user overview

Profile behavior:

- Logged-in user sees their own identity and session counts.
- Admin also sees:
  - total users
  - active users
  - active sessions
  - other users in system
  - send login SMS to users

Sessions are based on active PettyCash API tokens.

## 19. Settings / Role Management Flow

Routes:

- `GET /petty/settings`
- `POST /petty/settings/users`
- `PUT /petty/settings/users/{user}`

Purpose:

- This is the true user/role/permission admin page.

What admin can do here:

- create a petty user
- assign role
- set active/disabled state
- set/reset password
- manage phone number where supported
- send login SMS during creation
- edit an existing user
- switch role
- override default permissions with explicit permissions

Important protections:

- admin cannot accidentally remove their own admin role in unsafe ways
- admin cannot disable their own account in unsafe ways

Create user fields:

- `name`
- `email`
- `phone_no` if supported
- `role`
- `is_active`
- `password`
- `password_confirmation`

Update user controls:

- role
- status
- phone number if supported
- password reset
- explicit permission selection by permission group

This is the web source of truth for roles and permissions.

## 20. Typical End-to-End Operational Flows

### 20.1 Finance user recording incoming funds

1. Login
2. Open Credits
3. Create Credit
4. Enter:
   - reference
   - amount
   - date
   - transaction cost
   - description
5. Save
6. System creates a new batch
7. User may review the batch

### 20.2 Finance user recording bike fuel

1. Login
2. Open Motor Vehicles spendings
3. Click create
4. Choose:
   - auto or single batch
   - bike subtype = fuel
   - active bike
5. Enter:
   - Mpesa reference
   - amount
   - transaction cost
   - date
   - optional respondent
   - optional description
6. Save
7. System allocates from petty balances

### 20.3 Finance user recording bike maintenance

Same as bike fuel, but:

- `sub_type = maintenance`
- `particulars` becomes required

### 20.4 Finance user creating a respondent

1. Open Respondents
2. Click create
3. Enter:
   - name
   - phone
   - staff id
   - status
   - category
4. Save

### 20.5 Finance user creating a bike

1. Open Bikes Master
2. Click create
3. Enter:
   - plate number
   - model
   - status
4. Save
5. If active, it becomes selectable in bike spending

### 20.6 Token operator creating a hostel

1. Open Token Hostels
2. Click Add Hostel
3. Choose source:
   - ONT Site
   - Chained Hostel
4. Supply source-linked hostel details
5. Enter profile details:
   - contact person
   - phone number
   - router count
6. Save hostel
7. Continue to agreement setup
8. Later record token payments from hostel detail context

### 20.7 Admin managing roles

1. Open Settings
2. Create or select user
3. Assign role
4. Enable/disable user
5. Adjust explicit permissions
6. Save

### 20.8 Admin or finance reviewing statements

1. Open Ledger for full statement-style records
2. Filter by date, type, batch, or search term
3. Export PDF / CSV / Excel

If a formal summarized board report is needed:

1. Open Reports
2. Choose the relevant report
3. Apply report filters
4. Export

## 21. What the Mobile App Must Match

The web module is not just:

- one dashboard
- one add form
- one reports page

To truly match the web module, mobile needs to preserve:

- role-aware visibility
- separate operational flows for each spending family
- batch allocation behavior
- active-only selection of bikes/respondents for new spending
- maintenance-specific bike fields
- daily meal bill flow distinct from normal meal spending
- hostel creation tied to ONT/chained source logic
- hostel agreement follow-up flow
- token payments as spending, recorded from hostel context
- real ledger behavior for statements/exports
- admin user/role management from settings
- notification management, not just notification display

## 22. Recommended Use of This File

Use this document as the checklist when reviewing the mobile app:

1. Is the screen present in mobile?
2. Does it open from the correct place?
3. Does it capture the same fields?
4. Does it enforce the same rules?
5. Does it save into the same business flow?
6. Does it support the same downstream steps after creation?

If the answer is no, the mobile flow is not yet matching the web module.

## 23. Page-By-Page User Journey

This section is the practical flow from login onward.

## 24. Login Page

### Where user lands

- User lands on the PettyCash login page.

### What the page shows

- Email field
- Password field
- Remember me option
- Login button

### What the user does

1. Enter email
2. Enter password
3. Submit login

### Expected feedback

- If credentials are valid:
  - user is authenticated
  - user is redirected into the module dashboard
- If credentials are invalid:
  - validation/authentication error is shown on the same page
  - user remains on login page

### Next page

- Dashboard

## 25. Dashboard Page

### Where user lands

- Immediately after successful login, user lands on Dashboard.

### What the page shows

- Summary cards for financial state
  - credited
  - spent
  - balance
- Date range filter
  - `from`
  - `to`
- Category breakdown if allowed by permission
- Bike maintenance urgency information
  - overdue
  - due soon
  - never serviced

### What the page is for

- Gives user the current operating picture before they move into a working module.

### What actions are available

- Change date range
- Apply filter
- Reset date filter
- Jump into another module from navigation:
  - Credits
  - Batches
  - Spendings
  - Maintenance
  - Ledger
  - Reports
  - Notifications
  - Profile
  - Settings

### How the page operates

- Dashboard reloads based on selected date range.
- Totals and breakdown reflect filtered period.
- Data visibility depends on permission scope.

### Expected feedback

- Filtered numbers update after submission.
- If user lacks a permission slice, that part simply is not shown.

### Next pages user commonly goes to

- Credits if they want to record incoming money
- Spendings if they want to record usage
- Ledger if they want statement-like detail
- Reports if they want exports
- Maintenance if they want service operations

## 26. Credits List Page

### Where user lands

- User opens Credits from navigation.

### What the page shows

- Paginated list of credit records
- Search/filter area
  - `from`
  - `to`
  - `q`
  - `sort`
- Export action
- Create credit action

### What the page is for

- Review all money-in records.
- Start a new credit entry.
- Edit or export existing credit data.

### What actions are available

- Search by reference/description
- Filter by date range
- Sort by date or amount
- Export PDF/CSV/Excel
- Open create form
- Open edit form
- Delete credit if allowed

### How the page operates

- List refreshes with applied filters.
- Export uses current filter state.

### Expected feedback

- Success flash after create/update/delete
- Error flash if operation fails
- Export downloads file

### Next pages

- Create Credit page
- Edit Credit page
- Batch detail page after successful create

## 27. Create Credit Page

### Where user lands

- User clicks Create Credit from the credits list.

### What the page shows

- Credit form in a step-like layout
- Typical fields:
  - `reference`
  - `amount`
  - `date`
  - `transaction_cost`
  - `description`

### What the page is for

- Record new incoming funds into petty cash.

### What actions are available

- Fill credit data
- Submit save
- Go back to list

### How the page operates

- Validates required fields
- Creates credit record
- Creates a fresh batch linked to that funding

### Expected feedback

- On success:
  - success message shown
  - redirect goes to the created batch detail page
- On validation failure:
  - user stays on form
  - validation errors display

### Next page

- Batch detail page

## 28. Batch List and Batch Detail Pages

### Where user lands

- User opens Batches from navigation, or is redirected to a batch after saving a credit.

### What the batch list shows

- List of batches
- Batch numbers
- Balances and references depending on view

### What the batch detail shows

- One batch record
- Allocations/spend usage against that batch
- Remaining balance

### What the page is for

- Trace how incoming funds are grouped and then consumed by spendings.

### What actions are available

- Review available or spent batch balance
- Use batch number when recording spending in single-batch mode

### Expected feedback

- Mostly a review/drilldown page, not a heavy data-entry page

### Next pages

- Any spending create flow using selected batch
- Ledger or Reports for deeper review

## 29. Bike Spending List Page

### Where user lands

- User opens Motor Vehicles / Bike Spendings.

### What the page shows

- Paginated list of bike spending records
- Filters:
  - `from`
  - `to`
  - `sub_type`
  - `batch_id`
  - `q`
  - `sort`
- Total value for filtered records
- Create action
- Export action

### What the page is for

- Review all bike spending activity.
- Search by bike, respondent, reference, or particulars.
- Start new bike spending.

### What actions are available

- Create bike spending
- Filter/search/sort
- Export
- Open edit
- Open by-bike drilldown
- Delete if allowed

### Expected feedback

- Filtered records and total refresh
- Success flash after save/update/delete

### Next pages

- Create Bike Spending page
- Edit Bike Spending page
- Bike-specific drilldown page

## 30. Create Bike Spending Page

### Where user lands

- User clicks create from bike spendings list.

### What the page shows

- Multi-step style form with:
  - funding mode
  - batch if single
  - subtype
  - bike
  - payment details
  - respondent
  - description
  - particulars

### What the page is for

- Record fuel or maintenance spending for a bike.

### What actions are available

- Choose `funding`:
  - `auto`
  - `single`
- Choose `sub_type`:
  - `fuel`
  - `maintenance`
- Choose bike
- Enter:
  - reference
  - amount
  - transaction cost
  - date
  - respondent if needed
  - description
  - particulars
- Save

### How the page operates

- Only active bikes appear.
- Only active respondents appear.
- If subtype is maintenance, particulars becomes mandatory.
- Allocation is done automatically on save:
  - across balances if auto
  - to selected batch if single

### Expected feedback

- On success:
  - success flash
  - redirect back to bike spendings list
- On error:
  - same form reloads
  - validation or allocation message shown

### Next page

- Bike Spendings list

## 31. Meals List Page

### Where user lands

- User opens Meals from navigation.

### What the page shows

- Meal spending rows
- Filters:
  - `from`
  - `to`
  - `batch_id`
  - `q`
  - `sort`
- Total for filtered view
- Create action
- Export action

### What the page is for

- Review lunch or meal payment spendings.

### What actions are available

- Create meal spending
- Edit/delete if allowed
- Export
- Filter/search

### Next pages

- Create Meal page
- Edit Meal page

## 32. Create Meal Spending Page

### Where user lands

- User clicks create from meals page.

### What the page shows

- Form for ordinary meal spending
- Fields for:
  - funding mode
  - batch
  - reference
  - amount
  - transaction cost
  - date or range
  - description
  - mass mode support

### What the page is for

- Record one-off meal spending or mass disbursement across several days.

### What actions are available

- Choose single date or range
- Turn mass mode on/off
- Enter amount and description
- Save

### How the page operates

- In mass mode, one save may create multiple records, one per date.
- Auto funding checks enough total balance first.

### Expected feedback

- On success:
  - success flash says whether one meal or mass disbursement was recorded
- On error:
  - form stays open with validation or insufficient balance error

### Next page

- Meals list

## 33. Meal Daily Bills Page

### Where user lands

- User opens Meal Bills / Daily Meals from navigation.

### What the page shows

- Daily meal bills list
- Modal/forms for preparing daily entries
- Payment actions for selected unpaid daily bills

### What the page is for

- Prepare daily meal bills first, then settle them with payments later.

### What actions are available

- Create daily bill entries over a date range
- Attach respondents to each day
- Set daily amounts
- Select unpaid daily rows
- Calculate totals
- Post payment for selected daily entries
- Edit unpaid daily entries
- Edit/delete payment rows

### How the page operates

- This is not the same as normal meal spending.
- It is a staged billing-and-payment flow.
- Paid daily bills cannot be edited directly; payment must be edited instead.

### Expected feedback

- Totals preview updates during selection/building
- Save flashes success or validation errors

### Next pages

- Daily bill edit
- Daily payment edit

## 34. Token Hostels List Page

### Where user lands

- User opens Token Hostels from navigation.

### What the page shows

- Paginated hostel list
- Search and due filters
- Due status information
- Last payment state
- Agreement/family behavior
- Add Hostel action
- Gateway operations access

### What the page is for

- Manage all token hostels as operating accounts.
- Track who is due, overdue, terminated, or unpaid.

### What actions are available

- Search hostel, meter, phone, ONT identifiers
- Filter by due state
- Sort by due date
- Open hostel detail
- Add hostel
- Open gateway area

### Expected feedback

- Filtered hostel list updates
- Due reminders are reflected on page

### Next pages

- Add Hostel page
- Hostel detail page
- Gateway page

## 35. Add Hostel Page

### Where user lands

- User clicks Add Hostel from token hostels area.

### What the page shows

- Step 1 of 2 style hostel creation flow
- Create mode choice:
  - From ONT Site
  - Chained Hostel
- Source selection widgets
- Hostel profile fields

### What the page is for

- Create a hostel from real ONT/site context or chain it from a main site.

### What actions are available

- Choose source mode
- Search/select ONT or main source hostel
- Enter:
  - hostel name
  - contact person
  - phone number
  - router count
- Save

### How the page operates

- ONT/site data drives part of the setup.
- If chained mode is supported, the child hostel still depends on a main site relationship.
- After save, web flow continues into agreement setup.

### Expected feedback

- On success:
  - hostel is created
  - redirect goes to agreement page
- On error:
  - page stays open with validation or source errors

### Next page

- Hostel Agreement page

## 36. Hostel Agreement Page

### Where user lands

- User arrives here right after creating hostel, or later when opening agreement from hostel flow.

### What the page shows

- Agreement details for the hostel
- Any supported family/chained context
- Controls for updating or terminating agreement

### What the page is for

- Finalize the billing/agreement state for a hostel before its payment lifecycle continues.

### What actions are available

- Update agreement details
- Apply agreement history suggestions
- Terminate agreement if needed

### Expected feedback

- Success flash after update/termination
- Validation errors on same page if required data is missing

### Next pages

- Hostel detail page
- Token hostels list

## 37. Hostel Detail Page

### Where user lands

- User opens one hostel from token hostels list.

### What the page shows

- Hostel profile
- Payment history
- Due state
- Meter and contact details
- QR tools if enabled
- Pending credits / overpay context if present

### What the page is for

- Operate one hostel account in detail.

### What actions are available

- Record token payment
- Edit hostel details
- Generate/download QR
- Merge ONT / refresh ONT serial
- Apply overpay
- Store/sort pending credits
- Detach child hostel if supported
- Open gateway pay action

### Expected feedback

- Payment or update success messages
- Errors stay on same context

### Next pages

- Record Token Payment flow
- Edit Token Payment flow

## 38. Record Token Payment Flow

### Where user lands

- User records payment from a hostel context.

### What the page/process shows

- Payment form attached to a hostel
- Spending-like fields:
  - funding
  - batch
  - reference
  - amount
  - transaction cost
  - date
  - receiver details
  - notes
  - meter context

### What the page is for

- Record token money out as spending tied to a hostel.

### How the page operates

- Payment is not treated as a credit.
- It uses token/payment logic but belongs to the operational spending side.

### Expected feedback

- On success:
  - payment is saved
  - hostel payment history updates
- On error:
  - validation/allocation errors shown

### Next page

- Hostel detail page

## 39. Other Spendings Page

### Where user lands

- User opens Other Spendings from navigation.

### What the page shows

- List of other spending rows
- Filters/search
- Create and export actions

### What the page is for

- Record and review non-bike, non-meal, non-token spendings.

### What actions are available

- Create
- Edit
- Delete
- Export
- Filter/search

### Next pages

- Create Other Spending page

## 40. Create Other Spending Page

### Where user lands

- User clicks create from other spendings page.

### What the page shows

- Form with:
  - funding
  - batch
  - reference
  - amount
  - transaction cost
  - date
  - respondent
  - description

### What the page is for

- Record miscellaneous spending.

### How the page operates

- Optional respondent must still be active/selectable.
- Allocation happens on save.

### Expected feedback

- Success flash on save
- Validation/allocation errors if save fails

### Next page

- Other Spendings list

## 41. Bikes Master Page

### Where user lands

- User opens Bikes Master from navigation.

### What the page shows

- Bike records
- Create/edit/delete options

### What the page is for

- Maintain the available fleet records for spending and maintenance use.

### What actions are available

- Create bike
- Edit bike
- Delete bike

### Expected feedback

- Success flash after create/update/delete

### Next pages

- Create Bike page
- Edit Bike page

## 42. Respondents Page

### Where user lands

- User opens Respondents from navigation.

### What the page shows

- Respondent list
- Create action
- Respondent detail
- Edit/delete actions
- Card generation/SMS actions

### What the page is for

- Maintain cash holders / field staff / respondents used in the module.

### What actions are available

- Create respondent
- Edit respondent
- Open respondent detail
- Generate/share respondent card

### Expected feedback

- Success flash after changes
- SMS/card actions confirm with response messages

### Next pages

- Create Respondent page
- Respondent detail page

## 43. Maintenance Page

### Where user lands

- User opens Maintenance from navigation.

### What the page shows

- Tabbed maintenance area:
  - schedule
  - history
  - unroadworthy

### What the page is for

- Track vehicle service lifecycle.

### What actions are available

- Change tabs
- Open bike maintenance profile
- Create service record
- Edit/delete service record
- Mark/unmark unroadworthy

### Expected feedback

- Tab content changes
- Success flash after service updates

### Next pages

- Create Service page
- Bike Maintenance Detail page

## 44. Ledger Page

### Where user lands

- User opens Ledger from navigation.

### What the page shows

- Unified statement-like records across the petty cash system
- Filters:
  - date range
  - batch
  - type
  - search
- Export actions

### What the page is for

- This is the deepest operational review page.
- Users use it to inspect the full movement trail and export statements.

### What actions are available

- Filter by month or custom dates
- Search by reference and operational identifiers
- Export

### How the page operates

- Rebuilds the unified row set according to filters
- Pulls category/reference/source fields into one table

### Expected feedback

- Table and totals update with filters
- Export downloads file in chosen format

### Next pages

- Usually no create flow from here
- User typically goes to Reports, Credits, or Spendings after review

## 45. Reports Hub Page

### Where user lands

- User opens Reports from navigation.

### What the page shows

- Report hub
- Quick filters
- Quick export shortcuts
- Links into specific reports:
  - bike
  - respondent
  - batch
  - general/board

### What the page is for

- Start a formal report/export workflow.

### What actions are available

- Apply report filters
- Open report forms
- Export category-specific reports directly

### Expected feedback

- User either gets downloadable file or is taken to a more specific report form

### Next pages

- Bike Report page
- Respondent Report page
- Batch Report page
- General Report page

## 46. Notifications Page

### Where user lands

- User opens Notifications from navigation.

### What the page shows

- In-app notifications table
- unread count
- filter/search area
- mark-all-read action
- SMS settings/configuration actions
- admin contact management
- SMS template management

### What the page is for

- Monitor due/overdue/low-balance alerts
- Configure notification delivery behavior

### What actions are available

- Filter notifications
- Mark one read
- Mark all read
- Configure SMS settings
- Add/remove admin contacts
- Add/remove templates
- Run auto-check

### How the page operates

- It is both a feed and a configuration workspace.

### Expected feedback

- Read status changes
- Success/error flashes after config changes
- Auto-check returns result

### Next pages

- Usually remains within the same page using forms/modals/sections

## 47. Profile Page

### Where user lands

- User opens Profile from navigation.

### What the page shows

- Current user identity
- active session counts
- session table
- for admin:
  - all users summary
  - other users list
  - login SMS actions

### What the page is for

- Review current account and active sessions.

### What actions are available

- Review sessions
- For admin, send login SMS to another user

### Expected feedback

- SMS action success/error message

### Next pages

- Settings if admin needs role/permission management

## 48. Settings Page

### Where user lands

- Admin opens Settings from navigation.

### What the page shows

- Users table
- Create new user form
- Selected user permissions editor

### What the page is for

- Full petty-user administration.

### What actions are available

- Create user
- Set role
- Set phone
- Set password
- Enable/disable user
- Assign explicit permissions
- Save changes

### How the page operates

- One area lists users.
- Another area edits whichever user is selected.
- Role defaults can be left in place or overridden with explicit permissions.

### Expected feedback

- Success flash after create/update
- Validation errors displayed on same page
- Admin-safety restrictions prevent dangerous self-demotion/self-disable operations

### Next pages

- Back to Dashboard
- Back to operational pages once user/permissions are adjusted

## 49. Straight-Line Example Flows

### Flow A: Login to record bike fuel

1. Login page
2. Dashboard
3. Bike Spendings list
4. Create Bike Spending
5. Save
6. Return to Bike Spendings list with success message

### Flow B: Login to add new hostel and then charge token payment later

1. Login page
2. Dashboard
3. Token Hostels list
4. Add Hostel
5. Hostel Agreement
6. Hostel Detail
7. Later record Token Payment from that hostel
8. Return to Hostel Detail with updated payment history

### Flow C: Login to add funds and confirm batch

1. Login page
2. Dashboard
3. Credits list
4. Create Credit
5. Save
6. Batch detail page opens automatically

### Flow D: Login to manage user roles

1. Login page
2. Dashboard
3. Settings
4. Create/select user
5. Edit role and permissions
6. Save
7. Stay on Settings page with success feedback
