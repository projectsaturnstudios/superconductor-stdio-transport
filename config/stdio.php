<?php

return [
    'stream_management' => [
        'default' => 'native',
        'managers' => [
            'native' => [
                'driver' => 'native',
                'settings' => [
                    'read_byte_size' => 4096,
                ]
            ],
            'react' => [
                'driver' => 'react',
                'settings' => [
                    'loop_time' => 0.5, // seconds
                    'loop_interface' => \React\EventLoop\Loop::class,
                ]
            ],
        ]
    ]
];
