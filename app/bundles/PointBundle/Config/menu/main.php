<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.points.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_points_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-calculator'
        ),
        'display' => ($security->isGranted(array('point:points:viewown', 'point:points:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.point.menu.index' => array(
                'route'    => 'mautic_point_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_point_index'
                )
            )
        )
    )
);

return $items;
