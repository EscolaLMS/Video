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
    ],
    'queue' => env('VIDEO_QUEUE', 'queue-long-job'),
    'queue_connection' =>  env('VIDEO_QUEUE_CONNECTION', 'redis-long-job'),
];
