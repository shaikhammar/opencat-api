<?php

return [
    'max_upload_mb'       => (int) env('MAX_UPLOAD_MB', 50),
    'async_threshold_mb'  => (int) env('ASYNC_THRESHOLD_MB', 5),
    'file_retention_hours'=> (int) env('FILE_RETENTION_HOURS', 24),
    'deepl_api_key'       => env('DEEPL_API_KEY'),
    'google_translate_key'=> env('GOOGLE_TRANSLATE_API_KEY'),
];
