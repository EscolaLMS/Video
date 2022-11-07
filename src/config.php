<?php

return [
    'disk' => env('VIDEO_DISK', config('filesystems.default')),
    'enable' => env('VIDEO_PROCESSING_ENABLE', true),
    'bitrates' => [
        [
            'kiloBitrate' => 250,
            'scale' => '640:480'
        ],
        [
            'kiloBitrate' => 500,
            'scale' => '640:480'
        ],
    ]
];
