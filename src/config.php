<?php

return [
    'disk' => env('VIDEO_DISK', config('filesystems.default')),
    'enable' => env('VIDEO_PROCESSING_ENABLE', true),
    'bitrates' => [
        [
            'kiloBitrate' => 250,
        ],
        [
            'kiloBitrate' => 500,
        ],
        [
            'kiloBitrate' => 1000,
        ]
    ]
];
