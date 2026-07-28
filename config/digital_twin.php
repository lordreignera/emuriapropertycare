<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Point Cloud Conversion
    |--------------------------------------------------------------------------
    |
    | E57/LAS/LAZ source files are too heavy for direct browser rendering.
    | Configure these commands when the local machine or worker has PDAL and
    | PotreeConverter installed.
    |
    */

    'pdal_binary' => env('DIGITAL_TWIN_PDAL_BINARY', 'pdal'),
    'potree_converter_binary' => env('DIGITAL_TWIN_POTREE_CONVERTER_BINARY', 'PotreeConverter'),
    'conversion_timeout' => (int) env('DIGITAL_TWIN_CONVERSION_TIMEOUT', 3600),
    'upload_max_kilobytes' => (int) env('DIGITAL_TWIN_UPLOAD_MAX_KB', 102400),
];
