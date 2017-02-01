<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        ],
        'forms' => [
            'mautic.form.type.report' => [
                'class'     => 'Mautic\ReportBundle\Form\Type\ReportType',
                'arguments' => 'mautic.factory',
                'alias'     => 'report',
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
        ],
        'models' => [
            'mautic.report.model.report' => [
                'class'     => 'Mautic\ReportBundle\Model\ReportModel',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.template.formatter',
                    'mautic.helper.templating',
                    'mautic.channel.helper.channel_list',
                ],
            ],
        ],
    ],
];
