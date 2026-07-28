# Digital Twin Upload Formats

This document explains which capture sources can be attached to the ETOGO property digital twin, how staff upload them, and which formats need conversion before they can be viewed interactively in the browser.

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

## Supported Upload Formats

| Format | Extensions / Input | Typical Capture Provider | Browser Viewer | Converter Needed |
|---|---|---|---|---|
| Matterport hosted tour | Matterport SID or hosted URL | Matterport | Embedded hosted tour iframe | No |
| GLB / glTF model | `.glb`, `.gltf` | Phone camera, LiDAR, BIM/CAD, manual upload | Interactive Three.js 3D viewer | No |
| Image evidence | `.jpg`, `.jpeg`, `.png`, `.webp`, `.heic`, `.heif` | Phone camera, DSLR, drone, thermal, manual upload | Image preview | No, but HEIC/HEIF browser support may vary |
| PDF/document evidence | `.pdf` | RESOLV, BIM/CAD, inspection report, manual upload | Embedded PDF iframe | No |
| 360 panorama | `.jpg`, `.jpeg`, `.png`, `.webp` with twin layer `panorama_set` | 360 camera, Matterport export, manual upload | Interactive panorama viewer | No |
| Raw point cloud | `.e57`, `.las`, `.laz`, `.pts`, `.ptx`, `.xyz` | LiDAR, Matterport export, drone scan, survey capture | Stored first, converted to Potree tiles | Yes |
| Mesh/source package | `.obj`, `.fbx`, `.dae`, `.ply`, `.zip` | BIM/CAD, photogrammetry, manual upload | Stored as source evidence | Yes, but current converter does not process these yet |

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
- `.pts`
- `.ptx`
- `.xyz`

When one of these files is uploaded locally as a `Master Point Cloud`, the system queues a conversion job automatically.

Mesh/source packages are stored but not converted by the current local converter:

- `.obj`
- `.fbx`
- `.dae`
- `.ply`
- `.zip`

These can be retained as source evidence, but should be converted externally to `.glb`/`.gltf` or another supported runtime format before interactive browser viewing.

## Point Cloud Conversion

The current built-in point-cloud conversion flow uses:

- PDAL for `.e57`, `.pts`, `.ptx`, and `.xyz` normalization
- PotreeConverter for browser-ready Potree tile output

`.las` and `.laz` are passed directly to PotreeConverter.

Required environment settings:

```env
DIGITAL_TWIN_PDAL_BINARY=pdal
DIGITAL_TWIN_POTREE_CONVERTER_BINARY=PotreeConverter
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_UPLOAD_MAX_KB=102400
```

Run the queue worker with:

```bash
php artisan queue:work --queue=digital-twin,default
```

External point-cloud URLs can be stored as references, but the local converter currently needs an uploaded local file to process.

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

For raw point-cloud uploads, the source is saved and queued for conversion. For GLB/glTF, image, PDF, panorama, and Matterport hosted sources, the viewer can load them without a conversion step.

## Issue Markers

Staff can add issue markers from the same Property Digital Twin screen. Markers may be linked to:

- A spatial model
- A capture session
- A PHAR finding

For GLB/glTF layers, staff can click directly on the 3D model surface to fill marker coordinates before saving the marker.

## Current Limitations

- Large point-cloud files depend on PHP/WAMP upload limits and `DIGITAL_TWIN_UPLOAD_MAX_KB`.
- Point-cloud conversion requires local filesystem storage.
- PDAL and PotreeConverter must be installed and available to the queue worker.
- Mesh formats such as `.obj`, `.fbx`, `.dae`, and `.ply` are accepted as source evidence, but the current converter does not transform them to GLB.
- HEIC/HEIF uploads are accepted, but not all browsers preview them consistently.
