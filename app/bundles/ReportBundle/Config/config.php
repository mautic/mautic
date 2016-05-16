<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main' => array(
            'mautic_report_index'  => array(
                'path'         => '/reports/{page}',
                'controller'   => 'MauticReportBundle:Report:index'
            ),
            'mautic_report_export' => array(
                'path'       => '/reports/view/{objectId}/export/{format}',
                'controller' => 'MauticReportBundle:Report:export',
                'defaults'   => array(
                    'format' => 'csv'
                )
            ),
            'mautic_report_view'   => array(
                'path'         => '/reports/view/{objectId}/{reportPage}',
                'controller'   => 'MauticReportBundle:Report:view',
                'defaults'     => array(
                    'reportPage' => 1
                ),
                'requirements' => array(
                    'reportPage' => '\d+'
                )
            ),
            'mautic_report_action' => array(
                'path'         => '/reports/{objectAction}/{objectId}',
                'controller'   => 'MauticReportBundle:Report:execute'
            )
        ),
        'api'  => array(
            'mautic_api_getreports'   => array(
                'path'       => '/reports',
                'controller' => 'MauticReportBundle:Api\ReportApi:getEntities'
            ),
            'mautic_api_getreport'    => array(
                'path'       => '/reports/{id}',
                'controller' => 'MauticReportBundle:Api\ReportApi:getReport'
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'mautic.report.reports' => array(
                'route'     => 'mautic_report_index',
                'iconClass' => 'fa-line-chart',
                'access'    => array(
                    'report:reports:viewown',
                    'report:reports:viewother'
                ),
                'priority' => 20
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.report.search.subscriber' => array(
                'class'     => 'Mautic\ReportBundle\EventListener\SearchSubscriber'
            ),
            'mautic.report.report.subscriber' => array(
                'class'     => 'Mautic\ReportBundle\EventListener\ReportSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.report'          => array(
                'class'     => 'Mautic\ReportBundle\Form\Type\ReportType',
                'arguments' => 'mautic.factory',
                'alias'     => 'report'
            ),
            'mautic.form.type.filter_selector' => array(
                'class' => 'Mautic\ReportBundle\Form\Type\FilterSelectorType',
                'alias' => 'filter_selector'
            ),
            'mautic.form.type.table_order'     => array(
                'class'     => 'Mautic\ReportBundle\Form\Type\TableOrderType',
                'arguments' => 'mautic.factory',
                'alias'     => 'table_order'
            ),
            'mautic.form.type.report_filters'  => array(
                'class'     => 'Mautic\ReportBundle\Form\Type\ReportFiltersType',
                'arguments' => 'mautic.factory',
                'alias'     => 'report_filters'
            )
        )
    )
);
