# Client Issue And Change Request Workflow

## Goal

Add a client-facing way for an existing property owner to report:

- an urgent issue or emergency
- a non-urgent repair request
- a client-requested change to an existing property or active maintenance scope

The new flow should reuse the current quotation, approval, maintenance, and invoice pipeline instead of creating a second billing workflow.

## Current System Anchors

The current codebase already has the right building blocks:

- Client dashboard and sidebar already expose support-related navigation.
- A placeholder client emergency reports route already exists.
- Admin navigation already has project delivery, maintenance visit logs, billing, and a dormant change-order area.
- Invoicing is already centralized through the inspection invoice sync service.
- Maintenance execution is already tracked through maintenance visit logs tied to an inspection.

## Recommended Product Decision

Do not place this under quotation review.

Quotation review is for the original assessment flow before work starts.
Issue reporting should live under the client project experience, because this new request happens after a property is already onboarded and usually after a project or maintenance relationship already exists.

Recommended client entry points:

1. Client dashboard quick action: Report Issue / Request Change
2. Client projects page: per-project button named Report Issue / Request Change
3. Client properties page: property-level shortcut for clients who have a property but no active work yet
4. Keep Emergency Reports in Support only for tenant-style emergency access, not as the main owner workflow

## Recommended Domain Model

Create a new primary model:

- ServiceRequest

Purpose:

- one intake record for owner-submitted emergency, repair, and change requests
- tied to property first, and optionally tied to project and inspection when applicable

Suggested fields:

- id
- request_number
- user_id
- property_id
- project_id nullable
- inspection_id nullable
- source enum: client_dashboard, project_page, property_page, admin_created
- request_type enum: emergency, repair, change_request
- urgency enum: low, medium, high, critical
- title
- description
- requested_location nullable
- requested_changes json nullable
- photos json nullable
- floor_plan_pin json nullable
- preferred_visit_window nullable
- status enum: submitted, triaged, awaiting_assessment, assessed, quotation_shared, client_approved, in_progress, resolved, cancelled
- triage_notes nullable
- assessment_summary nullable
- quotation_id nullable
- approved_change_order_id nullable
- created_project_id nullable
- assigned_to nullable
- submitted_at
- triaged_at nullable
- assessed_at nullable
- resolved_at nullable

Why this is better than reusing only ChangeOrder or TenantEmergencyReport:

- ChangeOrder is too late in the workflow; it assumes an existing scoped project change and its controller is still empty.
- TenantEmergencyReport is tenant-specific and does not cover owner-requested remodel or scope changes.
- ServiceRequest becomes the intake layer. ChangeOrder remains the contractual change layer after admin assesses the request.

## Workflow

### 1. Client submits request

Client chooses property, then selects:

- Emergency
- Repair / Maintenance Issue
- Change Request / Upgrade

Required input:

- property
- request type
- urgency
- short title
- detailed description

Optional input:

- project or active work item
- room or area
- photos
- floorplan pin
- preferred access time

Rules:

- if request type is emergency and urgency is critical, highlight it on admin dashboard immediately
- if property has an active project, auto-link that project
- if property has an approved inspection scope, auto-link the latest approved inspection

### 2. Admin triage

Admin receives the request in a new queue:

- New Service Requests

Admin actions:

- acknowledge request
- classify as emergency, repair, or change request
- assign owner: project manager, inspector, or technician
- decide workflow path:
  - direct dispatch
  - assessment required
  - convert to change order
  - merge into existing active project

### 3. Assessment path

If pricing or scope is not yet known, admin creates a follow-up assessment.

Recommended system behavior:

- create a new inspection record linked to the same property and project when detailed assessment is required
- mark it with request context so staff know it originated from a service request
- pre-seed the assessment findings list with the exact issues the client reported so the assessor starts from those submitted findings, not from a blank assessment form
- allow the assessor to refine each client-reported finding into the normal finding structure by confirming category, subsystem, severity, and scope details
- keep a trace from each seeded assessment finding back to the originating service request item so admin can show the client that the quoted scope came from the reported problem
- after assessment, generate quotation using the existing inspection quotation flow

This preserves the current system logic for:

- findings
- finding-level material attachment
- finding-level estimated labour input
- BDC, FMC, FRLC, TRC, ARP, and related pricing computations
- approved findings selection
- pricing snapshots
- agreement and payment flow

Assessment pricing rule:

- client-reported items become the initial findings rows
- admin then attaches materials and estimated labour to those specific findings
- the platform computes BDC and the rest of the normal pricing stack from those assessed findings
- the quotation sent to the client is therefore based on the client-reported findings after admin validation and enrichment, not on a disconnected second list

### 4. Quotation path

After assessment, admin shares quotation to the client using the existing quotation flow.

Important clarification:

- the quotation should include only the assessed findings that came from the linked service request, unless admin explicitly adds a newly discovered related finding during assessment
- if admin adds a newly discovered related finding, it should be marked as an assessor-added finding so the client can distinguish what they reported from what was discovered during inspection

Client sees from dashboard/projects:

- request submitted
- assessment scheduled
- quotation ready
- quotation approved
- work in progress
- invoice available

### 5. Approval outcome

Two possible post-approval outcomes:

1. Repair or new discrete work:
   - continue through inspection quotation approval
   - create or reuse project work scope
   - sync invoice through InspectionInvoiceSyncService

2. Scope change on an already active project:
   - create a ChangeOrder record from the approved ServiceRequest
   - capture added cost and schedule impact
   - surface it under the existing Change Orders admin section

### 6. Delivery and closure

After approval:

- technician work is tracked with existing maintenance visit logs when tied to an inspection scope
- admin can log materials used
- material cost and BDC calculations should feed the same quotation/invoice path, not a separate invoice creator
- when resolved, service request status becomes resolved and links to the resulting inspection, change order, and invoice

## Attachment Points In Current Codebase

### Client side

Attach the feature here first:

- client dashboard quick actions
- client projects page card actions
- client sidebar support/services navigation

Recommended UI labels:

- Report Issue
- Request Property Change
- Emergency Help

Recommended screen set:

- client.service-requests.index
- client.service-requests.create
- client.service-requests.show

### Admin side

Add a new queue under Project Management or Services:

- Service Requests
- Emergency Requests
- Change Requests

Recommended admin screens:

- admin.service-requests.index
- admin.service-requests.show
- admin.service-requests.assess

This queue should sit before Change Orders in the workflow.

### Existing features to reuse

- Use InspectionQuotation for quote sharing and client approval
- Use MaintenanceVisitLog for work execution tracking after approval
- Use InspectionInvoiceSyncService for invoice persistence and updates
- Use ChangeOrder only after an assessed request alters existing project scope

## Status Model

Recommended request statuses:

- submitted
- triaged
- awaiting_assessment
- assessed
- quotation_shared
- client_approved
- in_progress
- resolved
- cancelled

Recommended admin dashboard counters:

- critical emergencies
- awaiting triage
- awaiting assessment
- awaiting client approval
- active request work

## Materials And Costing

For requests that become priced work:

- materials should be attached at the assessment or quotation stage at the finding level, not only after work starts
- estimated labour should also be attached at the same finding level before quotation is shared
- BDC and material cost should be recalculated through the same pricing service path already used by inspections
- invoice generation should still go through InspectionInvoiceSyncService

Avoid:

- manual standalone invoice creation from the request screen
- a second pricing formula separate from inspection pricing

## Implementation Phases

### Phase 1

- Add ServiceRequest model, migration, routes, controllers, and basic client/admin pages
- Add client submit form and admin triage queue
- Add dashboard badges and notifications

### Phase 2

- Add convert-to-assessment action from admin request detail page
- Link a service request to a new or existing inspection
- Show request state back to client

### Phase 3

- Add convert-to-change-order action for approved scope changes on active projects
- connect approved request to ChangeOrder records

### Phase 4

- Add richer floorplan pinning, tenant-originated escalation, SLA timers, and analytics

## Recommended First Build Slice

Build this first:

1. Client can submit ServiceRequest from dashboard and project page
2. Admin can see a Service Requests queue
3. Admin can triage each request and mark whether it needs assessment or becomes direct change order
4. If assessment is required, admin creates a linked inspection and then uses the current quotation flow

This is the smallest slice that fits the existing architecture and gets you from client complaint to quotation, approval, maintenance, and invoice without duplicating the billing logic.