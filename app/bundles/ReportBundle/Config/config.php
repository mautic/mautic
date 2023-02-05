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
            'mautic_api_getreports' => [
                'path'       => '/reports',
                'controller' => 'Mautic\ReportBundle\Controller\Api\ReportApiController::getEntitiesAction',
            ],
            'mautic_api_getreport' => [
                'path'       => '/reports/{id}',
                'controller' => 'Mautic\ReportBundle\Controller\Api\ReportApiController::getReportAction',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.report.reports' => [
                'route'     => 'mautic_report_index',
                'iconClass' => 'fa-line-chart',
                'access'    => [
                    'report:reports:viewown',
                    'report:reports:viewother',
                ],
                'priority' => 20,
            ],
        ],
    ],

    'services' => [
        'helpers' => [
            'mautic.report.helper.report' => [
                'class' => \Mautic\ReportBundle\Helper\ReportHelper::class,
                'alias' => 'report',
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
        ],
        'models' => [
            'mautic.report.model.report' => [
                'class'     => \Mautic\ReportBundle\Model\ReportModel::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.templating',
                    'mautic.channel.helper.channel_list',
                    'mautic.lead.model.field',
                    'mautic.report.helper.report',
                    'mautic.report.model.csv_exporter',
                    'mautic.report.model.excel_exporter',
                ],
            ],
            'mautic.report.model.csv_exporter' => [
                'class'     => \Mautic\ReportBundle\Model\CsvExporter::class,
                'arguments' => [
                    'mautic.helper.template.formatter',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.report.model.excel_exporter' => [
                'class'     => \Mautic\ReportBundle\Model\ExcelExporter::class,
                'arguments' => [
                    'mautic.helper.template.formatter',
                ],
            ],
            'mautic.report.model.scheduler_builder' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder::class,
                'arguments' => [
                    'mautic.report.model.scheduler_template_factory',
                ],
            ],
            'mautic.report.model.scheduler_template_factory' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory::class,
                'arguments' => [],
            ],
            'mautic.report.model.scheduler_date_builder' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Date\DateBuilder::class,
                'arguments' => [
                    'mautic.report.model.scheduler_builder',
                ],
            ],
            'mautic.report.model.scheduler_planner' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner::class,
                'arguments' => [
                    'mautic.report.model.scheduler_date_builder',
                    'doctrine.orm.default_entity_manager',
                ],
            ],
            'mautic.report.model.send_schedule' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Model\SendSchedule::class,
                'arguments' => [
                    'mautic.helper.mailer',
                    'mautic.report.model.message_schedule',
                    'mautic.report.model.file_handler',
                ],
            ],
            'mautic.report.model.file_handler' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Model\FileHandler::class,
                'arguments' => [
                    'mautic.helper.file_path_resolver',
                    'mautic.helper.file_properties',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.report.model.message_schedule' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Model\MessageSchedule::class,
                'arguments' => [
                    'translator',
                    'mautic.helper.file_properties',
                    'mautic.helper.core_parameters',
                    'router',
                ],
            ],
            'mautic.report.model.report_exporter' => [
                'class'     => \Mautic\ReportBundle\Model\ReportExporter::class,
                'arguments' => [
                    'mautic.report.model.schedule_model',
                    'mautic.report.model.report_data_adapter',
                    'mautic.report.model.report_export_options',
                    'mautic.report.model.report_file_writer',
                    'event_dispatcher',
                ],
            ],
            'mautic.report.model.schedule_model' => [
                'class'     => \Mautic\ReportBundle\Model\ScheduleModel::class,
                'arguments' => [
                    'doctrine.orm.default_entity_manager',
                    'mautic.report.model.scheduler_planner',
                ],
            ],
            'mautic.report.model.report_data_adapter' => [
                'class'     => \Mautic\ReportBundle\Adapter\ReportDataAdapter::class,
                'arguments' => [
                    'mautic.report.model.report',
                ],
            ],
            'mautic.report.model.report_export_options' => [
                'class'     => \Mautic\ReportBundle\Model\ReportExportOptions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.report.model.report_file_writer' => [
                'class'     => \Mautic\ReportBundle\Model\ReportFileWriter::class,
                'arguments' => [
                    'mautic.report.model.csv_exporter',
                    'mautic.report.model.export_handler',
                ],
            ],
            'mautic.report.model.export_handler' => [
                'class'     => \Mautic\ReportBundle\Model\ExportHandler::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.file_path_resolver',
                ],
            ],
        ],
        'validator' => [
            'mautic.report.validator.schedule_is_valid_validator' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Validator\ScheduleIsValidValidator::class,
                'arguments' => [
                    'mautic.report.model.scheduler_builder',
                ],
                'tag' => 'validator.constraint_validator',
            ],
        ],
        'command' => [
            'mautic.report.command.export_scheduler' => [
                'class'     => \Mautic\ReportBundle\Scheduler\Command\ExportSchedulerCommand::class,
                'arguments' => [
                    'mautic.report.model.report_exporter',
                    'translator',
                ],
                'tag' => 'console.command',
            ],
        ],
    ],

    'parameters' => [
        'report_temp_dir'                     => '%kernel.project_dir%/media/files/temp',
        'report_export_batch_size'            => 1000,
        'report_export_max_filesize_in_bytes' => 5000000,
        'csv_always_enclose'                  => false,
    ],
];
