<?php

return [
    'name'        => 'MauticAnalyticsBundle',
    'description' => 'Analytics bundle for Mautic - dwell time tracking and auto download tracking.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_analytics_page_leave' => [
                'path'       => '/mtc/leave',
                'controller' => 'MauticPlugin\MauticAnalyticsBundle\Controller\PageLeaveController::pageLeaveAction',
            ],
            'mautic_analytics_download_track' => [
                'path'       => '/mtc/download/track',
                'controller' => 'MauticPlugin\MauticAnalyticsBundle\Controller\DownloadTrackController::trackAction',
                'method'     => 'POST',
            ],
            'mautic_analytics_video_track' => [
                'path'       => '/mtc/video/track',
                'controller' => 'MauticPlugin\MauticAnalyticsBundle\Controller\VideoTrackController::trackAction',
                'method'     => 'POST',
            ],
        ],
    ],
];
