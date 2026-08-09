# MatterPak Digital Twin Flow

Updated: August 7, 2026

This document is the working map for the ETOGO MatterPak, property twin, issue marker, PHAR finding, and costing flow. Use it before editing the digital twin code so each capture stays linked to the correct property and diagnosis record.

## Scope

ETOGO treats MatterPak as a private source package, not as a Matterport Showcase clone. A MatterPak ZIP contains export files such as OBJ, MTL, texture images, XYZ point cloud data, JPG/PDF floor plans, and supporting PDFs. The app converts the OBJ/MTL/textures into a browser-ready GLB, preserves the other source files, and shows the GLB, plans, documents, texture gallery, and sampled point-cloud preview in the inspection digital twin viewer.

Matterport Showcase features such as hosted sweep navigation and proprietary dollhouse transitions are only available when using a hosted Matterport tour URL or model SID. The ZIP export does not contain that full hosted Showcase experience.

For best inspection quality, attach both sources when available:

- Matterport hosted URL/SID for the clear photographic walkthrough.
- MatterPak ZIP for OBJ/MTL/textures, floor plans, point cloud source records, GLB conversion, and 3D marker placement.

## End-To-End Flow

1. Staff opens the property twin from either `properties/{property}/digital-twin` or `inspections/{inspection}/digital-twin`.
2. `properties/{property}/digital-twin` resolves the correct inspection/diagnosis first. If more than one diagnosis exists, the user is sent to the inspection selector.
3. `inspections/{inspection}/digital-twin` opens the single working viewer for that diagnosis. This is why a URL such as `/inspections/5/digital-twin` can be correct even when it is the first active diagnosis for the property: `5` is the database inspection record ID, while the UI shows the property-relative diagnosis number.
4. Staff uploads a capture source from the same viewer screen.
5. For S3/R2-backed production storage, `DigitalTwinController::createDirectUpload` gives the browser a temporary private-bucket upload URL. The browser uploads the source file directly to the bucket with progress, then submits the capture form with a signed completion token. Local/non-S3 disks can still use the multipart fallback.
6. `DigitalTwinController::storeSpatialModel` validates the request or signed direct-upload token, classifies the source, records the original private storage path, and creates a `CaptureSession`.
7. For a MatterPak ZIP, the controller creates a parent `TwinSourceFile` with `file_role = matterpak_archive` and a `TwinProcessingJob` with `job_type = matterpak_obj_to_glb`.
8. If the same upload also includes a Matterport hosted URL or SID, the controller creates a ready `SpatialModel` with `source_type = hosted_tour` for the same capture session. This gives the viewer a photographic walkthrough while the ZIP conversion continues.
9. Staff can also click `Convert to GLB`, `Retry GLB`, or `Reconvert GLB` on an existing MatterPak archive. The button creates a fresh conversion job unless one is already queued or processing for that archive.
10. The job is queued on `digital-twin`; run it locally with `php artisan queue:work --queue=digital-twin,default --timeout=3600 --tries=1`.
11. `ProcessMatterPakToGlb` extracts the ZIP into temporary storage, skips macOS metadata and unsafe paths, and writes child `TwinSourceFile` rows for OBJ, MTL, textures, XYZ, floor plans, reflected ceiling plans, and supporting PDFs.
12. The worker samples the MatterPak XYZ file into `point-cloud-preview.json`. The full XYZ remains preserved as a source file.
13. The worker selects the largest OBJ mesh, checks the MTL texture references, records source-texture diagnostics, runs Blender, and exports `model.glb` with the `matterpak_visual_preserve` profile. That profile keeps original images when Blender supports it, uses maximum image quality when re-encoding is unavoidable, exports materials/UVs/normals/tangents, and disables Draco mesh compression.
14. The generated GLB is saved privately under the property and inspection path, then a ready `SpatialModel` is created or updated for the capture session.
15. The viewer receives all displayable sources from `show.blade.php` and renders them through `resources/js/digital-twin-viewer.js`.
16. Staff can click the GLB surface to set marker coordinates, create/link a PHAR finding, and later add labour, materials, trade pricing, quotation approval, and costing through the existing PHAR workflow.

## Do Not Duplicate Views

Keep one canonical digital twin workspace:

- Main inspection viewer: `resources/views/inspections/digital-twin/show.blade.php`
- Inspection selector for property-level entry: `resources/views/inspections/digital-twin/select-inspection.blade.php`
- Evidence summary partial used in reports: `resources/views/inspections/partials/digital-twin-evidence-summary.blade.php`

Do not create separate MatterPak-only pages, property-only twin pages, or PHAR-only twin pages. Add modes, buttons, source tabs, and marker behavior to the existing viewer so every record stays tied to the same property and diagnosis.

## File Map For Edits

Routes:

- `routes/web.php`: property digital twin route, inspection digital twin route, direct upload URL route, capture completion/upload route, source download route, marker route, and the legacy Matterport redirect.

Upload, access, and controller flow:

- `app/Http/Controllers/DigitalTwinController.php`: main place to edit digital twin behavior. Handles `show`, `showProperty`, `storeSpatialModel`, manual MatterPak conversion/reconversion, `storeIssueMarker`, download endpoints, source classification, marker-to-PHAR creation, access checks, and property-relative diagnosis numbering.
- `app/Http/Requests/StoreSpatialModelRequest.php`: upload validation, supported extensions, provider/model URL checks, ZIP readability, and required source rules.
- `app/Http/Requests/StoreIssueMarkerRequest.php`: marker validation, PHAR finding link/create fields, camera JSON fields, coordinates, severity, and status.
- `app/Services/MatterportHostedTourService.php`: shared hosted Matterport walkthrough creation and legacy `matterport_models` sync. Use this instead of duplicating hosted-tour `CaptureSession`, `SpatialModel`, and `MatterportModel` creation in controllers.
- `config/digital_twin.php`: supported formats, MIME rules, MatterPak role keywords, queue name, timeout, temporary path, Blender binary, storage disk, upload max size, and point-cloud preview sample size.

MatterPak conversion:

- `app/Jobs/ProcessMatterPakToGlb.php`: ZIP extraction, child source records, MatterPak role classification, point-cloud preview generation, source-texture diagnostics, MTL texture-reference diagnostics, quality-preserving Blender export script, GLB storage, ready `SpatialModel` creation, and failed/ready job state.
- `.env`: local runtime values such as `DIGITAL_TWIN_BLENDER_BINARY`, `DIGITAL_TWIN_DISK`, `DIGITAL_TWIN_PROCESSING_QUEUE`, `DIGITAL_TWIN_CONVERSION_TIMEOUT`, and `DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS`.

Models and database records:

- `app/Models/CaptureSession.php`: one capture event per upload/source set.
- `app/Models/TwinSourceFile.php`: original archives, extracted source records, source downloads, and generated point-cloud previews.
- `app/Models/TwinProcessingJob.php`: queued/processing/ready/failed conversion job state.
- `app/Models/SpatialModel.php`: browser-ready layers such as hosted Matterport tours, GLB/glTF files, images, PDFs, panoramas, and generated MatterPak GLB.
- `app/Models/IssueMarker.php`: digital twin markers with positions, camera context, source references, and optional PHAR finding links.
- `app/Models/PHARFinding.php`: diagnosis findings that markers can link to or create.
- `database/migrations/2026_07_20_000001_create_vendor_neutral_digital_twin_tables.php`: base capture/session/spatial model/marker tables.
- `database/migrations/2026_08_05_000001_create_twin_source_files_table.php`: source file records.
- `database/migrations/2026_08_05_000002_add_context_fields_to_issue_markers_table.php`: marker context fields.
- `database/migrations/2026_08_05_000003_add_matterpak_metadata_to_twin_source_files_table.php`: MatterPak source metadata.
- `database/migrations/2026_08_05_000004_create_twin_processing_jobs_table.php`: processing jobs.

Viewer UI and front end:

- `resources/views/inspections/digital-twin/show.blade.php`: toolbar, upload form, capture cards, source buttons, viewer JSON payload, issue marker panel, PHAR finding fields, conversion quality strip, and layout sizing.
- `resources/js/digital-twin-viewer.js`: Three.js GLB viewer, material/texture quality settings, orbit/walk camera controls, keyboard arrows, fullscreen, marker picking, image/PDF/panorama/media gallery/point-cloud preview rendering.
- `vite.config.js`: includes `resources/js/digital-twin-viewer.js` as a Vite entry.

Reports and client/admin surfaces:

- `resources/views/inspections/partials/digital-twin-evidence-summary.blade.php`: digital twin evidence shown in client/admin reports.
- `resources/views/admin/inspections/findings-preview.blade.php`: admin findings preview with digital twin evidence summary.
- `resources/views/admin/inspections/assessment-report.blade.php`: admin assessment report with evidence summary.
- `resources/views/client/inspections/findings-report.blade.php`: client findings report with evidence summary.
- `resources/views/client/inspections/report.blade.php`: client inspection report with evidence summary.
- `resources/views/admin/properties/show.blade.php`, `resources/views/admin/properties/index.blade.php`, `resources/views/client/properties/index.blade.php`, and `resources/views/client/inspections/index.blade.php`: entry buttons into the property/inspection twin.

PHAR, costing, and approval:

- `app/Http/Controllers/InspectionController.php`: PHAR data, findings preview, assessment finalisation, findings sharing, quotation sharing, follow-up quotation sharing, completion, and work payment endpoints.
- `resources/views/admin/inspections/form-phar-data.blade.php`: PHAR finding/costing workspace. Digital twin findings should land here as normal PHAR findings after marker creation.
- `app/Services/PharTradePricingService.php`: approved trade partner pricing used while costing PHAR findings.
- `app/Services/MergeBridgeCalculator.php`: downstream pricing alignment after quotation approval.
- `resources/views/shared/inspection-job-approval-agreement.blade.php`: agreement scope after client approval.
- `app/Notifications/QuotationSharedNotification.php` and `app/Notifications/ClientQuotationApprovedNotification.php`: quote review notifications.

Client invoice/payment flow:

- `app/Http/Controllers/PropertyController.php`: admin property facts and diagnosis invoice creation/sharing.
- `app/Services/PropertyProcessInvoiceService.php`: property process invoice lines and sharing.
- `app/Http/Controllers/Client/InspectionController.php`: client-side inspection/work payment flow.
- `app/Services/InstallmentPaymentService.php`: idempotent partial/installment payment recording.
- `app/Services/InspectionInvoiceSyncService.php`: keeps inspection/invoice payment state aligned.
- `app/Http/Controllers/StripeWebhookController.php`: Stripe webhook validation and payment intent handling.
- `database/migrations/2026_08_06_000001_add_installment_payment_intent_ids_to_inspections_table.php`: stores processed installment payment intent IDs.

Pricing guardrail: leave the `$1` test amounts in place while testing. Do not change pricing defaults from digital twin or MatterPak edits unless the task is explicitly about pricing.

Tests:

- `tests/Feature/DigitalTwinVendorNeutralTest.php`: vendor-neutral upload, viewer, marker, MatterPak, and source-file behavior.
- `tests/Feature/MatterportInspectionViewerTest.php`: legacy Matterport route/view compatibility.
- `tests/Feature/ClientInspectionScheduleTest.php`: client schedule/payment intent guard.
- `tests/Feature/InstallmentPaymentServiceTest.php`: payment intent idempotency.
- `tests/Feature/PropertyDiagnosisInvoiceFlowTest.php`: property facts and diagnosis invoice flow.

## Storage Layout

Private storage is scoped by property and inspection:

```text
properties/{property_id}/twins/inspections/{inspection_id}/source/{random}.zip
properties/{property_id}/twins/inspections/{inspection_id}/extracted-source-files/matterpak-{source_file_id}/run-{job_id}-{run_id}/{file}
properties/{property_id}/twins/inspections/{inspection_id}/processed/matterpak-{source_file_id}/model.glb
properties/{property_id}/twins/inspections/{inspection_id}/processed/matterpak-{source_file_id}/point-cloud-preview.json
properties/{property_id}/twins/inspections/{inspection_id}/thumbnails/{file}
```

MatterPak source roles currently used:

- `matterpak_archive`: original uploaded ZIP.
- `obj_mesh`: extracted OBJ mesh.
- `material_library`: extracted MTL file.
- `texture`: extracted texture map images used by the OBJ/MTL.
- `colour_point_cloud`: preserved XYZ point cloud.
- `floor_plan`: MatterPak floor plan JPG/PDF.
- `reflected_ceiling_plan`: MatterPak ceiling plan JPG/PDF.
- `supporting_source`: readme or other supporting source files.
- `point_cloud_preview`: generated sampled JSON preview from the XYZ file.

## Local Runbook

Check the Blender path in `.env`:

```env
DIGITAL_TWIN_BLENDER_BINARY=C:/wamp64/www/EMURIAREGENERATIVEPROPERTYCARE/tools/blender/blender-4.3.2-windows-x64/blender.exe
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS=30000
```

Run the MatterPak worker:

```bash
php artisan queue:work --queue=digital-twin,default --timeout=3600 --tries=1
```

Rebuild front-end assets after viewer JavaScript changes:

```bash
npm.cmd run build
```

Run focused tests after digital twin or MatterPak edits:

```bash
vendor\bin\phpunit --do-not-cache-result tests\Feature\DigitalTwinVendorNeutralTest.php tests\Feature\MatterportInspectionViewerTest.php
```

Run payment/client flow tests after pricing, invoice, Stripe, or approval edits:

```bash
vendor\bin\phpunit --do-not-cache-result tests\Feature\ClientInspectionScheduleTest.php tests\Feature\InstallmentPaymentServiceTest.php tests\Feature\PropertyDiagnosisInvoiceFlowTest.php
```

For local development only, re-run an existing MatterPak job from Tinker by replacing `1` with the real `twin_processing_jobs.id`:

```bash
php artisan tinker --execute="(new \App\Jobs\ProcessMatterPakToGlb(1))->handle();"
```

## Laravel Cloud Deployment

Use Laravel Cloud for the web app, database, queues, and private Object Storage. Do not rely on the application container's local filesystem for uploaded MatterPak ZIPs, extracted files, generated point-cloud previews, or GLBs.

Recommended environment values:

```env
APP_ENV=production
FILESYSTEM_DISK=s3
DIGITAL_TWIN_DISK=s3
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=4200
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS=30000
```

Laravel Cloud runs Linux containers, so the local Windows value must not be used there. If Blender is bundled into the project image or installed during the build phase, set a Linux path such as:

```env
DIGITAL_TWIN_BLENDER_BINARY=tools/blender/blender
```

The job resolves relative Blender paths from the Laravel project root before running the queue worker.

Worker command:

```bash
php artisan queue:work --queue=digital-twin,default --timeout=3600 --tries=1
```

Best production approach for heavy MatterPak files:

- Laravel Cloud web app prepares a temporary private-bucket upload URL, the browser uploads the ZIP directly to Object Storage, and Laravel receives only the completion token before creating `TwinProcessingJob`.
- A dedicated worker process handles the `digital-twin` queue with higher memory/CPU than the web app.
- If Laravel Cloud cannot include a reliable Blender binary in the worker image, keep Laravel Cloud as the app/queue owner and run Blender in a separate converter service/container. That service reads the same private object-storage file, writes the GLB back to the configured disk, and updates the same database job/model records or calls a signed internal endpoint.

## Quality Checklist

Before calling the flow complete, confirm these points:

- Upload redirects back to `inspections/{inspection}/digital-twin` and the upload form collapses after success.
- The capture card appears under the correct property and diagnosis.
- The original MatterPak ZIP remains private and downloadable only through the source-file route.
- The queue job reaches `ready`, not `failed`.
- The generated GLB exists and the capture card shows a non-zero GLB size.
- Source texture diagnostics show the expected number and size of extracted texture images.
- Texture-reference diagnostics show mapped textures and ideally `0 missing`.
- A `point_cloud_preview` source file exists when the ZIP contains an XYZ file.
- The source toolbar includes the GLB, plans, PDFs, texture gallery, and point-cloud preview when those files exist.
- A MatterPak upload with a hosted Matterport URL/SID also shows a hosted walkthrough source.
- The viewer is large enough, supports toolbar buttons, and responds to arrow keys.
- Clicking the GLB surface fills marker coordinates.
- Creating a marker can create or link a PHAR finding for the same inspection.
- PHAR costing, quotation sharing, client approval, and locked approved pricing still use the normal PHAR workflow.

## Known Limits

- MatterPak ZIP conversion gives us a browser GLB plus extracted source media; it does not reproduce every hosted Matterport Showcase behavior.
- The point-cloud view is a sampled preview for browser speed. The full XYZ is preserved for a later Potree, Cesium, PDAL, or tiled point-cloud pipeline.
- Large GLB files may need Draco/Meshopt compression or progressive loading later.
- Generic OBJ/ZIP uploads are preserved as source evidence unless they are classified as MatterPak ZIPs.
- Production should use persistent object storage for `DIGITAL_TWIN_DISK`; local filesystem storage is fine for WAMP development.
