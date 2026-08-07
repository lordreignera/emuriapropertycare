# Digital Twin Upload Formats

This document explains which capture sources can be attached to the ETOGO property digital twin, how staff upload them, and which formats need conversion before they can be viewed interactively in the browser.

For the complete MatterPak-to-viewer-to-PHAR runbook and edit map, see `docs/MATTERPAK_DIGITAL_TWIN_FLOW.md`.

## Where Uploads Happen

Capture sources are uploaded from the Property Digital Twin screen:

- Route: `inspections/{inspection}/digital-twin`
- View: `resources/views/inspections/digital-twin/show.blade.php`
- Form section: `Add Capture Source`
- Controller: `App\Http\Controllers\DigitalTwinController@storeSpatialModel`
- Request validation: `App\Http\Requests\StoreSpatialModelRequest`

Clients can view their own property twin, but upload and marker tools are only available to authorized staff.

## Who Can Upload

The following users can add capture sources when they are allowed for the inspection:

- Super Admin
- Administrator
- Assigned Project Manager
- Staff with `manage digital twin models`
- Staff with `attach matterport models`

Assigned staff checks include the inspection inspector, property inspector, property project manager, or project manager.

## Required Upload Data

Every capture source must include at least one of:

- Source file
- External URL
- Provider model ID

Matterport sources must include either:

- Matterport model SID
- Matterport hosted URL, such as `https://my.matterport.com/show/?m=MODEL_ID`
- MatterPak ZIP export

## Supported Upload Formats

| Format | Extensions / Input | Typical Capture Provider | Browser Viewer | Converter Needed |
|---|---|---|---|---|
| Matterport hosted tour | Matterport SID or hosted URL | Matterport | Embedded hosted tour iframe | No |
| MatterPak source package | `.zip` containing OBJ/MTL/textures, XYZ, JPG/PDF plans | Matterport | GLB after queued Blender conversion | Yes |
| GLB / glTF model | `.glb`, `.gltf` | Phone camera, LiDAR, BIM/CAD, manual upload | Interactive Three.js 3D viewer | No |
| Image evidence | `.jpg`, `.jpeg`, `.png`, `.webp` | Phone camera, DSLR, drone, thermal, manual upload | Image preview | No |
| PDF/document evidence | `.pdf` | RESOLV, BIM/CAD, inspection report, manual upload | Embedded PDF iframe | No |
| 360 panorama | `.jpg`, `.jpeg`, `.png`, `.webp` with twin layer `panorama_set` | 360 camera, Matterport export, manual upload | Interactive panorama viewer | No |
| Raw point cloud | `.e57`, `.las`, `.laz` | LiDAR, drone scan, survey capture | Preserved as source file | Later |
| Mesh/source package | `.obj`, `.zip` | BIM/CAD, photogrammetry, manual upload | Stored as source evidence | Yes |

## Formats That Do Not Need Conversion

These are browser-viewable immediately after upload or entry:

- Matterport hosted URLs or SIDs
- `.glb`
- `.gltf`
- `.jpg`
- `.jpeg`
- `.png`
- `.webp`
- `.pdf`

Panorama files use normal image extensions, but they should be uploaded with `Twin layer` set to `Panorama Set` so the viewer opens the panorama renderer.

## Formats That Need Conversion

Raw point clouds need conversion before browser viewing:

- `.e57`
- `.las`
- `.laz`

When one of these files is uploaded as a `Master Point Cloud`, the system preserves the original source file and marks it `awaiting_processing`. ETOGO does not attempt to open E57/LAS/LAZ directly in Three.js.

Mesh/source packages are stored as source files:

- `.obj`
- `.zip`

MatterPak ZIP packages can be converted by the queued Blender worker when Blender is configured. Generic OBJ uploads are preserved as source packages and can be converted externally or by a later adapter.

## MatterPak Conversion

The current MatterPak flow uses:

- The original MatterPak ZIP as the immutable source package.
- Extracted `OBJ`, `MTL`, texture images, `XYZ`, floor-plan images, and PDF plan files as child `twin_source_files`.
- A sampled `point-cloud-preview.json` source record generated from the MatterPak `XYZ` file when present.
- A `twin_processing_jobs` row with `job_type = matterpak_obj_to_glb`.
- Blender on a Laravel queue worker to convert OBJ/MTL/textures to a browser-ready GLB.
- An existing `spatial_models` row only after GLB generation succeeds.

Required environment settings:

```env
DIGITAL_TWIN_DISK=s3
DIGITAL_TWIN_BLENDER_BINARY=/path/to/blender
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS=30000
DIGITAL_TWIN_UPLOAD_MAX_KB=102400
```

Run the queue worker with:

```bash
php artisan queue:work --queue=digital-twin,default --timeout=3600 --tries=1
```

On Laravel Cloud, use Laravel Object Storage through the `s3` disk for persistent source files. The application filesystem is temporary and should only be used for extraction/conversion working files during one job.

## Point Cloud Processing

Point-cloud processing is intentionally deferred:

- E57/LAS/LAZ uploads are preserved as private source files.
- They are marked `awaiting_processing`.
- No Potree/Cesium/PDAL conversion is attempted in this phase.
- Future point-cloud workers must create browser-streamable output and then attach it as an existing `spatial_models` layer.

## How Staff Upload A Capture Source

1. Open the inspection.
2. Open `Digital Twin`.
3. In `Add Capture Source`, select the capture provider.
4. Select the twin layer.
5. Select the capture type.
6. Add a source file, external URL, or provider model ID.
7. Fill advanced capture details if available: original format, runtime format, device, capture date, accuracy class, thumbnail, and notes.
8. Choose whether the source should be the primary twin layer.
9. Click `Add Capture Source`.

For MatterPak uploads, the source is saved and queued for Blender conversion if the queue worker is configured. For raw point-cloud uploads, the source is preserved and marked `awaiting_processing`. For GLB/glTF, image, PDF, panorama, and Matterport hosted sources, the viewer can load them without a conversion step.

## Issue Markers

Staff can add issue markers from the same Property Digital Twin screen. Markers may be linked to:

- A spatial model
- A capture session
- A PHAR finding

For GLB/glTF layers, staff can click directly on the 3D model surface to fill marker coordinates before saving the marker.

## Current Limitations

- Large point-cloud files depend on PHP/WAMP upload limits and `DIGITAL_TWIN_UPLOAD_MAX_KB`.
- Laravel Cloud production should use Object Storage/S3 for `DIGITAL_TWIN_DISK`.
- Blender must be installed and available to the queue worker before MatterPak conversion can complete.
- Generic OBJ uploads are preserved as source evidence; the current automatic Blender conversion is scoped to MatterPak ZIP.
- E57/LAS/LAZ point-cloud conversion is planned for a later phase.
- MatterPak `XYZ` files produce a sampled browser preview; the complete point cloud is still preserved as source evidence.
