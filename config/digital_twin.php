<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Digital Twin Source Storage
    |--------------------------------------------------------------------------
    |
    | ETOGO stores property twin metadata and cloud references. Heavy capture
    | files, point-cloud conversion, and browser-ready tiles should be managed
    | in Azure, AWS, Matterport, or another external twin-processing service.
    |
    */

    'disk' => env(
        'DIGITAL_TWIN_DISK',
        env('APP_ENV') === 'production' ? env('FILESYSTEM_DISK', 'local') : 'local'
    ),

    'upload_max_kilobytes' => (int) env('DIGITAL_TWIN_UPLOAD_MAX_KB', 102400),

    'source_types' => [
        'glb',
        'gltf',
        'obj_bundle',
        'e57',
        'las',
        'laz',
        'image',
        'panorama',
        'pdf',
        'other',
    ],

    'processing_statuses' => [
        'uploading',
        'uploaded',
        'awaiting_processing',
        'queued',
        'processing',
        'ready',
        'failed',
        'cancelled',
    ],

    'supported_extensions' => [
        'glb',
        'gltf',
        'obj',
        'zip',
        'e57',
        'las',
        'laz',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
    ],

    'extension_source_types' => [
        'glb' => 'glb',
        'gltf' => 'gltf',
        'obj' => 'obj_bundle',
        'zip' => 'obj_bundle',
        'e57' => 'e57',
        'las' => 'las',
        'laz' => 'laz',
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'webp' => 'image',
        'pdf' => 'pdf',
    ],

    'mime_types' => [
        'glb' => ['model/gltf-binary', 'application/octet-stream'],
        'gltf' => ['model/gltf+json', 'application/json', 'text/plain', 'application/octet-stream'],
        'obj' => ['text/plain', 'application/octet-stream', 'model/obj'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'e57' => ['application/octet-stream'],
        'las' => ['application/octet-stream'],
        'laz' => ['application/octet-stream'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
    ],

    'processing' => [
        'queue' => env('DIGITAL_TWIN_PROCESSING_QUEUE', 'digital-twin'),
        'timeout_seconds' => (int) env('DIGITAL_TWIN_CONVERSION_TIMEOUT', 3600),
        'temporary_path' => env('DIGITAL_TWIN_TEMP_PATH', storage_path('app/private/tmp/digital-twin')),
    ],

    'blender' => [
        'binary' => env('DIGITAL_TWIN_BLENDER_BINARY', 'blender'),
    ],

    'matterpak' => [
        'archive_source_type' => 'obj_bundle',
        'generated_model_name' => 'MatterPak browser-ready GLB',
        'point_cloud_extensions' => ['xyz'],
        'model_extensions' => ['obj'],
        'material_extensions' => ['mtl'],
        'texture_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff', 'bmp'],
        'document_extensions' => ['pdf'],
        'plan_keywords' => ['floorplan', 'floor-plan', 'floor_plan', 'colorplan', 'color-plan', 'color_plan', 'reflected', 'ceiling', 'rcp'],
        'point_cloud_preview_points' => (int) env('DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS', 30000),
    ],
];
