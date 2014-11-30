<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$items = array();

$point   = $security->isGranted('point:points:view');
$trigger = $security->isGranted('point:triggers:view');

if ($point || $trigger) {
    $items['mautic.points.menu.root'] = array(
        'id'        => 'mautic_points_root',
        'iconClass' => 'fa-calculator',
        'children'  => array()
    );

    if ($point) {
        $items['mautic.points.menu.root']['children']['mautic.point.menu.index'] = array(
            'route' => 'mautic_point_index'
        );
    }

    if ($trigger) {
        $items['mautic.points.menu.root']['children']['mautic.point.trigger.menu.index'] = array(
            'route' => 'mautic_pointtrigger_index'
        );
    }

    //add category level
    \Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems($items['mautic.points.menu.root']['children'], 'point', $security);
}

return array(
    'priority' => 8,
    'items'    => $items
);