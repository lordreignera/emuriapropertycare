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

    'upload_max_kilobytes' => (int) env('DIGITAL_TWIN_UPLOAD_MAX_KB', 102400),
];
