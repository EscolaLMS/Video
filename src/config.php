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
    'queue' => [
        'connection' => [
            'name' => 'video_process_job',
            'config' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => env('REDIS_QUEUE', 'default'),
                'retry_after' => 1,
                'block_for' => null,
            ],
        ]
    ]
];
