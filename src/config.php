<?php

return [
    'disk' => env('VIDEO_DISK', config('filesystems.default')),
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
