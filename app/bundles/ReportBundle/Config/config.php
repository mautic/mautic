<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Form\Type\ReportType;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Command\ExportSchedulerCommand;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\EventListener\ReportSchedulerSubscriber;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Mautic\ReportBundle\Scheduler\Validator\ScheduleIsValidValidator;

return [
    'routes' => [
        'main' => [
            'mautic_report_index' => [
                'path'       => '/reports/{page}',
                'controller' => 'MauticReportBundle:Report:index',
            ],
            'mautic_report_export' => [
                'path'       => '/reports/view/{objectId}/export/{format}',
                'controller' => 'MauticReportBundle:Report:export',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_report_view' => [
                'path'       => '/reports/view/{objectId}/{reportPage}',
                'controller' => 'MauticReportBundle:Report:view',
                'defaults'   => [
                    'reportPage' => 1,
                ],
                'requirements' => [
                    'reportPage' => '\d+',
                ],
            ],
            'mautic_report_schedule_preview' => [
                'path'       => '/reports/schedule/preview/{isScheduled}/{scheduleUnit}/{scheduleDay}/{scheduleMonthFrequency}',
                'controller' => 'MauticReportBundle:Schedule:index',
                'defaults'   => [
                    'isScheduled'            => 0,
                    'scheduleUnit'           => '',
                    'scheduleDay'            => '',
                    'scheduleMonthFrequency' => '',
                ],
            ],
            'mautic_report_action' => [
                'path'       => '/reports/{objectAction}/{objectId}',
                'controller' => 'MauticReportBundle:Report:execute',
            ],
        ],
        'api' => [
            'mautic_api_getreports' => [
                'path'       => '/reports',
                'controller' => 'MauticReportBundle:Api\ReportApi:getEntities',
            ],
            'mautic_api_getreport' => [
                'path'       => '/reports/{id}',
                'controller' => 'MauticReportBundle:Api\ReportApi:getReport',
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
        'events' => [
            'mautic.report.search.subscriber' => [
                'class'     => 'Mautic\ReportBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.report.model.report',
                ],
            ],
            'mautic.report.report.subscriber' => [
                'class'     => 'Mautic\ReportBundle\EventListener\ReportSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.report.dashboard.subscriber' => [
                'class'     => 'Mautic\ReportBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.report.model.report',
                    'mautic.security',
                ],
            ],
            'mautic.report.scheduler.report_scheduler_subscriber' => [
                'class'     => ReportSchedulerSubscriber::class,
                'arguments' => [
                    'mautic.report.model.scheduler_planner',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.report' => [
                'class'     => ReportType::class,
                'arguments' => [
                    'mautic.report.model.report',
                    'translator',
                ],
            ],
            'mautic.form.type.filter_selector' => [
                'class' => 'Mautic\ReportBundle\Form\Type\FilterSelectorType',
                'alias' => 'filter_selector',
            ],
            'mautic.form.type.table_order' => [
                'class'     => 'Mautic\ReportBundle\Form\Type\TableOrderType',
                'arguments' => 'mautic.factory',
                'alias'     => 'table_order',
            ],
            'mautic.form.type.report_filters' => [
                'class'     => 'Mautic\ReportBundle\Form\Type\ReportFiltersType',
                'arguments' => 'mautic.factory',
                'alias'     => 'report_filters',
            ],
            'mautic.form.type.report_dynamic_filters' => [
                'class' => 'Mautic\ReportBundle\Form\Type\DynamicFiltersType',
                'alias' => 'report_dynamicfilters',
            ],
            'mautic.form.type.report_widget' => [
                'class'     => 'Mautic\ReportBundle\Form\Type\ReportWidgetType',
                'alias'     => 'report_widget',
                'arguments' => 'mautic.report.model.report',
            ],
            'mautic.form.type.aggregator' => [
                'class'     => 'Mautic\ReportBundle\Form\Type\AggregatorType',
                'alias'     => 'aggregator',
                'arguments' => 'translator',
            ],
            'mautic.form.type.report.settings' => [
                'class' => \Mautic\ReportBundle\Form\Type\ReportSettingsType::class,
                'alias' => 'report_settings',
            ],
        ],
        'helpers' => [
            'mautic.report.helper.report' => [
                'class' => \Mautic\ReportBundle\Helper\ReportHelper::class,
                'alias' => 'report',
            ],
        ],
        'models' => [
            'mautic.report.model.report' => [
                'class'     => ReportModel::class,
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
                'class'     => CsvExporter::class,
                'arguments' => [
                    'mautic.helper.template.formatter',
                ],
            ],
            'mautic.report.model.excel_exporter' => [
                'class'     => ExcelExporter::class,
                'arguments' => [
                    'mautic.helper.template.formatter',
                ],
            ],
            'mautic.report.model.scheduler_builder' => [
                'class'     => SchedulerBuilder::class,
                'arguments' => [
                    'mautic.report.model.scheduler_template_factory',
                ],
            ],
            'mautic.report.model.scheduler_template_factory' => [
                'class'     => SchedulerTemplateFactory::class,
                'arguments' => [],
            ],
            'mautic.report.model.scheduler_date_builder' => [
                'class'     => DateBuilder::class,
                'arguments' => [
                    'mautic.report.model.scheduler_builder',
                ],
            ],
            'mautic.report.model.scheduler_planner' => [
                'class'     => SchedulerPlanner::class,
                'arguments' => [
                    'mautic.report.model.scheduler_date_builder',
                    'doctrine.orm.default_entity_manager',
                ],
            ],
            'mautic.report.model.report_exporter' => [
                'class'     => ReportExporter::class,
                'arguments' => [
                    'doctrine.orm.default_entity_manager',
                    'mautic.report.model.report_data_adapter',
                ],
            ],
            'mautic.report.model.report_data_adapter' => [
                'class'     => ReportDataAdapter::class,
                'arguments' => [
                    'mautic.report.model.report',
                ],
            ],
        ],
        'validator' => [
            'mautic.report.validator.schedule_is_valid_validator' => [
                'class'     => ScheduleIsValidValidator::class,
                'arguments' => [
                    'mautic.report.model.scheduler_builder',
                ],
                'tag' => 'validator.constraint_validator',
            ],
        ],
        'command' => [
            'mautic.report.command.export_scheduler' => [
                'class'     => ExportSchedulerCommand::class,
                'arguments' => [
                    'mautic.report.model.report_exporter',
                ],
                'tag' => 'console.command',
            ],
        ],
    ],
];
