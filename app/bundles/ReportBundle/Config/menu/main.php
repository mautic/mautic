<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.report.report.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_report_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-suitcase'
        ),
        'display' => ($security->isGranted(array('report:reports:viewown', 'report:reports:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.report.report.menu.index' => array(
                'route'    => 'mautic_report_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_report_index'
                )
            ),
            'mautic.report.report.menu.new' => array(
                'route'    => 'mautic_report_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_report_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.report.report.menu.edit' => array(
                'route'           => 'mautic_report_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_report_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
        )
    )
);

return $items;
