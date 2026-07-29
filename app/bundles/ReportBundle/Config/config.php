<?php

return [
    'routes' => [
        'main' => [
            'mautic_report_index' => [
                'path'       => '/reports/{page}',
                'controller' => 'Mautic\ReportBundle\Controller\ReportController::indexAction',
            ],
            'mautic_report_export' => [
                'path'       => '/reports/view/{objectId}/export/{format}',
                'controller' => 'Mautic\ReportBundle\Controller\ReportController::exportAction',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_report_download' => [
                'path'       => '/reports/download/{reportId}/{format}',
                'controller' => 'Mautic\ReportBundle\Controller\ReportController::downloadAction',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_report_view' => [
                'path'       => '/reports/view/{objectId}/{reportPage}',
                'controller' => 'Mautic\ReportBundle\Controller\ReportController::viewAction',
                'defaults'   => [
                    'reportPage' => 1,
                ],
                'requirements' => [
                    'reportPage' => '\d+',
                ],
            ],
            'mautic_report_schedule_preview' => [
                'path'       => '/reports/schedule/preview/{isScheduled}/{scheduleUnit}/{scheduleDay}/{scheduleMonthFrequency}',
                'controller' => 'Mautic\ReportBundle\Controller\ScheduleController::indexAction',
                'defaults'   => [
                    'isScheduled'            => 0,
                    'scheduleUnit'           => '',
                    'scheduleDay'            => '',
                    'scheduleMonthFrequency' => '',
                ],
            ],
            'mautic_report_schedule' => [
                'path'       => '/reports/schedule/{reportId}/now',
                'controller' => 'Mautic\ReportBundle\Controller\ScheduleController::nowAction',
            ],
            'mautic_report_action' => [
                'path'       => '/reports/{objectAction}/{objectId}',
                'controller' => 'Mautic\ReportBundle\Controller\ReportController::executeAction',
            ],
        ],
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
