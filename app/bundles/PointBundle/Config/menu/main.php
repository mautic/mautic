<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
                ),
                'children' => array(
                    'mautic.point.menu.new' => array(
                        'route'    => 'mautic_point_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_point_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.point.menu.edit' => array(
                        'route'           => 'mautic_point_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_point_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.point.menu.view' => array(
                        'route'           => 'mautic_point_action',
                        'routeParameters' => array("objectAction"  => "view"),
                        'extras'  => array(
                            'routeName' => 'mautic_point_action|view'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            ),
            'mautic.point.trigger.menu.index' => array(
                'route'    => 'mautic_pointtrigger_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_pointtrigger_index'
                ),
                'children' => array(
                    'mautic.point.trigger.menu.new' => array(
                        'route'    => 'mautic_pointtrigger_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_pointtrigger_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.point.trigger.menu.edit' => array(
                        'route'           => 'mautic_pointtrigger_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_pointtrigger_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.point.trigger.menu.view' => array(
                        'route'           => 'mautic_pointtrigger_action',
                        'routeParameters' => array("objectAction"  => "view"),
                        'extras'  => array(
                            'routeName' => 'mautic_pointtrigger_action|view'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems(
    $items['mautic.points.menu.root']['children'],
    'point',
    $security
);

return array(
    'priority' => 8,
    'items'    => $items
);