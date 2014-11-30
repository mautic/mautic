<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('report:reports:viewown', 'report:reports:viewother'), 'MATCH_ONE')) {
    return array();
}

return array(
    'priority' => 11,
    'items'    => array(
        'mautic.report.report.menu.root' => array(
            'route'     => 'mautic_report_index',
            'iconClass' => 'fa-line-chart'
        )
    )
);

