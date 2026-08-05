# Current System Flow Map

Updated: August 5, 2026

This document describes the current ETOGO flow after the shift from a Matterport-first, pay-before-inspection model to a vendor-neutral property facts and diagnosis model.

## Product Position

ETOGO is a vendor-neutral property facts, digital twin, diagnosis, remediation, and stewardship platform.

Matterport, RESOLV, phone cameras, DSLR cameras, 360 cameras, drones, LiDAR, thermal cameras, BIM/CAD exports, PDFs, photos, and manual uploads are treated as capture sources. The application owns the property record, property facts, diagnosis records, issue markers, invoices, remediation workflow, and verification history.

## Naming Rule

Use this product wording in the UI and client-facing documents:

- Property Facts
- Property Twin
- Floor Plan
- Diagnosis
- PHAR Assessment / PHAR Findings where the technical assessment form is meant
- Remediation
- Verification
- Stewardship

Some database tables, controllers, routes, and model classes still use `Inspection` because they existed before the wording change. For now, treat `Inspection` in code as the operational diagnosis/assessment record. Do not create duplicate parallel diagnosis tables until the workflow is stable.

## Current End-to-End Flow

1. Client registers a property.
2. The client does not pay during property registration.
3. The admin dashboard shows the new registered property.
4. ETOGO contacts the client by phone or other direct communication.
5. Admin or assigned staff creates/assigns the operational diagnosis record behind the scenes.
6. ETOGO visits the property and captures property facts.
7. Staff uploads vendor-neutral capture outputs into the property twin workspace.
8. The property twin workspace stores capture sessions, spatial models, and optional issue markers.
9. Admin prepares a property facts and diagnosis invoice.
10. The client sees the invoice in the client portal.
11. The client pays the invoice through Stripe, either full payment or partial payment options such as 30% or 50%.
12. Staff continues with PHAR diagnosis findings, evidence, reports, client decisions, remediation pricing, work orders, payment, completion logs, and verification.

## Client Property Registration

Client route group:

- `GET /client/properties/create`
- `POST /client/properties`
- `GET /client/properties/{property}`
- `GET /client/properties/{property}/digital-twin`

Controller:

- `App\Http\Controllers\Client\PropertyController`

What happens now:

- Client submits property details, owner details, property type, units, size, known problems, property photos, and optional blueprint/floor-plan file.
- The property is saved with `status = registered`.
- A property code is generated.
- Admin-facing roles receive a property registration notification.
- Client is redirected to the property page with a message saying ETOGO will contact them, capture property facts, and prepare the diagnosis invoice.

Important rule:

- Property registration is intake only.
- Do not force Stripe payment during this step.
- Property photos, known problem images, and blueprint files are intake evidence, not the authoritative digital twin by themselves.

## Admin Property Review and Assignment

Admin routes:

- `GET /properties`
- `GET /properties/{property}`
- `PATCH /properties/{property}`
- Property assignment action handled by `PropertyController::assign`

Controller:

- `App\Http\Controllers\PropertyController`

What happens now:

- Admin, project manager, inspector, or technician can see properties according to role rules.
- Admin sees registered properties and can assign a project manager, inspector, and technician.
- Assignment creates or reuses a `Project`.
- Assignment creates or reuses an `Inspection` record as the current operational diagnosis record.
- If the property is still `registered`, assignment moves it to `awaiting_inspection`.

Current property statuses:

- `registered`: client has onboarded the property; ETOGO has not yet progressed it.
- `awaiting_inspection`: staff assignment or diagnosis setup has begun. Product wording should present this as awaiting diagnosis or awaiting assessment where possible.
- `in_assessment`: diagnosis/assessment work is underway.
- `assessed`: diagnosis/assessment has been completed.
- `archived`: property is no longer active.

Implementation note:

- The status value `awaiting_inspection` is still used in code and filters. Rename it later only through a controlled migration and route/view cleanup.

## Property Facts and Property Twin Capture

Main route:

- `GET /inspections/{inspection}/digital-twin`

Property-level route:

- `GET /properties/{property}/digital-twin`

Data upload routes:

- `POST /inspections/{inspection}/digital-twin/models`
- `POST /inspections/{inspection}/digital-twin/markers`

Controller:

- `App\Http\Controllers\DigitalTwinController`

Core tables/models:

- `capture_sessions`
- `spatial_models`
- `twin_source_files`
- `twin_processing_jobs`
- `issue_markers`
- `matterport_models` for legacy Matterport compatibility

Current supported capture providers:

- Manual Upload
- Matterport
- RESOLV
- Phone Camera
- DSLR / Mirrorless
- 360 Camera
- Drone
- LiDAR Scanner
- Thermal Camera
- BIM / CAD

Current supported capture types:

- Hosted Tour
- GLB / glTF Model
- OBJ / Mesh Package
- Point Cloud
- 360 Panorama
- Photo Set
- Video Walkthrough
- Thermal Scan
- Wall Scan
- BIM / CAD Model
- Document / Report

How capture is stored:

- A `CaptureSession` records who captured/uploaded the source, provider, capture type, device details, accuracy class, capture date, status, and metadata.
- A `TwinSourceFile` records original uploads and extracted package files: storage disk/path, original filename, checksum, extension, source type, file role, processing status, and processing errors.
- A `SpatialModel` records only displayable model/evidence layers or browser-ready derivatives: provider, source type, runtime format, original format, hosted URL, generated/uploaded file, thumbnail, status, processing status, primary flag, accuracy class, coordinate transform, and metadata.
- A `TwinProcessingJob` records conversion work such as MatterPak OBJ-to-GLB processing.
- Matterport is optional. If provider is Matterport and a provider model ID exists, a legacy `MatterportModel` row is also updated for backward compatibility.

Current viewer behavior:

- Hosted Matterport URLs can still be displayed as hosted walkthroughs.
- GLB/glTF is recognized as a Three.js model type.
- Images and PDFs are treated as viewable evidence.
- MatterPak ZIP is stored privately, extracted into source-file metadata, and queued for Blender OBJ-to-GLB conversion where a worker is configured.
- E57, LAS, and LAZ are accepted as preserved source files and marked `awaiting_processing`; they are not opened directly in Three.js.
- Generic OBJ/ZIP uploads are preserved as source packages unless a browser-ready derivative exists.

Current limitation:

- The application stores vendor-neutral model/evidence records now.
- GLB/glTF can be viewed immediately.
- MatterPak conversion requires Blender to be installed on the queue worker.
- E57/LAS/LAZ point-cloud processing is still a later processing-worker phase.

## MatterPak Processing

Supported MatterPak source:

- ZIP export containing OBJ mesh, MTL/material files, texture images, XYZ colour point cloud, and JPG/PDF floor plans.

Current conversion target:

- GLB model, stored as a browser-ready `SpatialModel` layer with `source_type = runtime_3d_model` and `runtime_format = glb`.

How it works:

1. Staff uploads a MatterPak ZIP from the digital twin workspace.
2. The original ZIP is stored privately through Laravel Storage.
3. A parent `twin_source_files` record is created for the archive.
4. A `twin_processing_jobs` record is created with `job_type = matterpak_obj_to_glb`.
5. The worker extracts the ZIP into a temporary job folder and creates child `twin_source_files` records for OBJ, MTL, textures, XYZ, JPG and PDF files.
6. The worker uses Blender to convert OBJ/MTL/textures into GLB.
7. The generated GLB is uploaded to private storage.
8. A browser-ready `spatial_models` record is created or updated only after GLB generation succeeds.
9. If Blender or source files are missing, the job/source records are marked `failed` and no ready spatial model is created.

Worker command:

```bash
php artisan queue:work --queue=digital-twin,default --timeout=3600 --tries=1
```

Environment keys:

```env
DIGITAL_TWIN_DISK=s3
DIGITAL_TWIN_BLENDER_BINARY=/path/to/blender
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
```

Local status:

- The MatterPak upload and queue feature exists.
- The queue worker still needs Blender installed/configured before real conversion can complete.
- On Laravel Cloud, use Object Storage/S3 for persistent twin files. The app filesystem is temporary and should only hold per-job extraction files.

## Point-Cloud Processing

Current point-cloud behavior:

- E57/LAS/LAZ uploads are stored as `twin_source_files`.
- Processing status is set to `awaiting_processing`.
- No PDAL, Potree, Cesium, or point-cloud tiling is attempted in this phase.
- Future point-cloud processing should create browser-streamable output and attach it through the existing `spatial_models` table.

## Issue Markers and PHAR Findings

Main model:

- `App\Models\IssueMarker`

Marker fields include:

- property
- inspection/diagnosis record
- spatial model
- capture session
- optional PHAR finding
- source provider
- marker type
- title
- severity
- status
- x/y/z position
- surface normal
- camera position and target
- clicked object UUID
- room name
- surface label
- source reference
- confidence
- attachments
- description

Current behavior:

- Staff can manually add issue markers from the digital twin workspace.
- For GLB/glTF layers, staff can click the 3D model surface and the viewer saves x/y/z, camera data, optional surface normal, and optional clicked object UUID.
- Markers can be linked to a PHAR finding through `phar_finding_id`.
- Clients can view their own property twin but cannot manage models or markers.

## Property Facts and Diagnosis Invoice

Admin route:

- `POST /properties/{property}/diagnosis-invoice`

Controller:

- `App\Http\Controllers\PropertyController::createDiagnosisInvoice`

Pricing service:

- `App\Services\DiagnosisPricingService`

What happens now:

- Admin enters or confirms a `property_facts_amount`.
- Admin enters or accepts the calculated `diagnosis_amount`.
- The diagnosis amount uses the existing inspection-style pricing calculation.
- The system creates or reuses the related project.
- The system creates or reuses the operational `Inspection` diagnosis record.
- The system creates or updates an invoice with line items for:
  - `property_facts`: Property Facts - floor plan and digital twin capture
  - `property_diagnosis`: Property Diagnosis Fee
- The invoice type is currently `additional`.
- The invoice status becomes `sent`, `partial`, or `paid` depending on paid amount and balance.

Current diagnosis pricing rule:

- Base fee: 299 per unit.
- High pitched roof surcharge: 75.
- Crawl space surcharge: 50.
- Stripe test charge setting remains 100 cents in the pricing service for test mode.

Important business rule:

- The old inspection fee computation is preserved and reused for the diagnosis fee.
- The client pays after ETOGO has prepared or delivered property facts, not at the property registration step.

## Client Invoice Payment

Client routes:

- `GET /client/invoices`
- `GET /client/invoices/{invoice}`
- `GET /client/invoices/{invoice}/payment?plan=30`
- `GET /client/invoices/{invoice}/payment?plan=50`
- `GET /client/invoices/{invoice}/payment?plan=full`
- `POST /client/invoices/{invoice}/payment`
- `GET /client/invoices/{invoice}/download`

Controller:

- `App\Http\Controllers\Client\InvoiceController`

Payment behavior:

- Client can view all their invoices.
- Client can pay the full invoice balance.
- Client can pay 50% of the invoice total, capped by remaining balance.
- Client can pay 30% of the invoice total, capped by remaining balance.
- Payment is processed through Stripe PaymentIntents.
- `InvoicePaymentService` applies payment amount, payment reference, paid amount, balance, and status.

Invoice sync behavior:

- Client invoice listing still syncs old inspection-fee invoices for legacy records.
- It skips creating a duplicate inspection-fee invoice when a property facts/diagnosis invoice already exists for the project.

## Diagnosis / PHAR Assessment After Invoicing

Main admin routes:

- `GET /inspections`
- `GET /inspections/{inspection}`
- `GET /inspections/{inspection}/phar-data`
- `POST /inspections/{inspection}/store-phar-data`
- estimation, quotation, schedule, and completion routes under `/inspections/{inspection}`

Controller:

- `App\Http\Controllers\InspectionController`

Current operational phases inside the `Inspection` model:

- `scheduled`
- `in_progress`
- `findings_captured`
- `findings_shared`
- `client_committed`
- `estimation_in_progress`
- `estimation_completed`
- `quotation_shared`
- `quotation_approved`
- `completed`

What happens after property facts:

- Staff captures PHAR diagnosis data.
- PHAR findings are saved.
- Evidence can be attached to findings.
- Findings report can be shared with the client.
- Client decides what to do now, defer, monitor, or request a quotation.
- Staff prepares remediation pricing and a quotation.
- Client responds to the quotation.
- Approved scope moves into remediation setup and work scheduling.

## Remediation and Verification

Core areas:

- `InspectionQuotation`
- `InspectionTradePricingItem`
- `RemediationRoadmap`
- `RemediationWorkOrder`
- `MaintenanceVisitLog`
- `VerificationRecord`
- `VerifiedPropertyFact`
- `StewardshipPlan`
- `PerformanceRecord`

Current flow:

- Findings become priced scope.
- Approved scope becomes work setup.
- Tools, technicians, schedules, and visit logs support execution.
- Completed visits and completed findings drive progress.
- Supervisor or ETOGO signoff verifies the work.
- Verified facts and performance records build the long-term stewardship record.

## Reports

Current report surfaces:

- Client inspection/diagnosis report pages.
- Client findings report.
- Client invoice PDF.
- Admin assessment/report views.
- Performance, savings, and operational reporting screens.

Report rule:

- Reports should describe the property facts, diagnosis findings, evidence, client decisions, remediation scope, costs, and verification outcomes.
- Reports should not imply that Matterport is required.
- Digital twin links in reports should point to the vendor-neutral digital twin route.

## Legacy Flow Still Present

These client routes still exist:

- `GET /client/inspections/{property}/schedule`
- `POST /client/inspections/{property}/payment-intent`
- `POST /client/inspections/{property}/schedule`
- checkout success/cancel routes

These routes represent the old client-driven schedule-and-pay-first inspection flow.

Current recommendation:

- Keep them temporarily for existing data and testing.
- Hide or de-emphasize them from the primary client journey.
- Move the main client call-to-action from "Schedule Inspection" to "View Property / Await ETOGO Contact / View Invoice" depending on property state.
- Later rename or replace them with diagnosis terminology after the backend flow is stable.

## Dashboards

Client dashboard should prioritize:

- My Properties
- Property status overview
- Property twin availability
- Invoices and payment status
- Diagnosis/report updates
- Recent activity

Admin dashboard should prioritize:

- New registered properties
- Properties awaiting assignment/contact
- Properties awaiting property facts capture
- Properties with property facts/diagnosis invoices to prepare
- Unpaid invoices
- Diagnoses in progress
- Findings awaiting client decision
- Remediation setup and active remediation

Language alignment:

- Primary client/dashboard/report wording now uses "Diagnosis" where it refers to the client/business service.
- Keep "PHAR Assessment" only where it specifically means the technical assessment form/report.
- Keep database/code names for now unless doing a deliberate migration.

## Source of Truth Rules

- `properties` is the client-owned property intake and long-term property record.
- `projects` groups operational work for a property.
- `inspections` currently represents the diagnosis/assessment job.
- `capture_sessions` records capture events and devices.
- `twin_source_files` records immutable original/source capture files and extracted package members.
- `twin_processing_jobs` records conversion status for source packages such as MatterPak.
- `spatial_models` records vendor-neutral browser/viewer layers and ready derivatives.
- `issue_markers` records spatial issue locations and can link to PHAR findings.
- `phar_findings` records diagnosis findings.
- `invoices` records billable property facts, diagnosis, remediation, and other charges.
- `maintenance_visit_logs` and verification records track execution and proof of work.

Do not store canonical issue data only in JavaScript, hosted Matterport tags, screenshots, or vendor temporary IDs. Laravel must remain the database source of truth.

## Migration Deployment Note

The current migration set is treated as the deployment baseline after consolidating older incremental changes into the main create migrations. A fresh SQLite migration run passed on July 24, 2026. Before deploying to Laravel Cloud, do not restore deleted incremental migrations unless the target database already has those exact migration names recorded in its `migrations` table. For a new production database, run the current baseline as-is; for an existing production database, compare the production `migrations` table first and use additive migrations only.

## Current Gaps To Address Next

1. Continue secondary UI wording cleanup in deeply nested admin/legacy pages as they are touched.
2. Hide or replace the old pay-before-schedule client flow from the main journey.
3. Add explicit database states for property facts capture: pending, captured, twin ready, invoice prepared.
4. Add direct issue-marker evidence upload.
5. Add floor-plan/twin delivery status and client notification.
6. Add invoice line-item templates for property facts and diagnosis.
7. Add production large-file upload/import path for S3-hosted camera/scanner outputs.
8. Add E57/LAS/LAZ point-cloud processing after choosing Potree/Cesium worker architecture.

Recently addressed:

- Admin dashboard queues now surface awaiting contact, property facts pending, invoice needed, and diagnosis in progress.
- GLB/glTF digital twin viewer supports click-on-model issue marker placement with Three.js raycasting.
- Reports now present a property lifecycle summary covering property facts, diagnosis, spatial markers, remediation, and verification.
- Reports now include uploaded twin source files and processing status, not only ready viewer layers.
- MatterPak ZIP uploads are stored privately, recorded in `twin_source_files`, and queued through `twin_processing_jobs` for Blender OBJ-to-GLB conversion.

## Recommended Immediate Flow

Use this as the team-facing process until the remaining UI rename work is complete:

1. Client registers property.
2. Admin reviews registered property.
3. Admin calls client and confirms site visit.
4. Admin assigns project manager/inspector.
5. Staff captures property facts on site using available camera/scanner.
6. Staff uploads outputs to the digital twin workspace.
7. Staff marks the best source as primary where applicable.
8. Admin creates property facts and diagnosis invoice.
9. Client pays invoice through the client portal.
10. Staff completes diagnosis/PHAR data capture and shares findings/report.
11. Client chooses approved/declined/deferred findings or requests remediation quotation.
12. Admin prepares remediation pricing and quotation.
13. Client approves and pays according to selected payment plan.
14. ETOGO executes work, logs progress, verifies completion, and updates stewardship records.
