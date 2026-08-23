<?php

declare(strict_types=1);

return [
    'routes' => [
        'api' => [
            'mautic_api_reportsstandard' => [
                'standard_entity' => true,
                'name'            => 'reports',
                'path'            => '/reports',
                'controller'      => Mautic\ReportBundle\Controller\Api\ReportApiController::class,
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.report.reports' => [
                'route'     => 'mautic_report_index',
                'iconClass' => 'ri-file-chart-2-fill',
                'access'    => [
                    'report:reports:viewown',
                    'report:reports:viewother',
                ],
                'priority' => 20,
            ],
        ],
    ],

    'parameters' => [
        'report_temp_dir'                     => '%mautic.application_dir%/media/files/temp',
        'report_export_batch_size'            => 1000,
        'report_export_max_filesize_in_bytes' => 5_000_000,
        'csv_always_enclose'                  => false,
    ],
];
