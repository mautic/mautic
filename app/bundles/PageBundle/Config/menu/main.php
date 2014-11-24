<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.page.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_page_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-file-text-o'
        ),
        'display' => ($security->isGranted(array('page:pages:viewown', 'page:pages:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.page.menu.index' => array(
                'route'    => 'mautic_page_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_page_index'
                ),
            ),
            'mautic.page.menu.new' => array(
                'route'    => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.page.menu.edit' => array(
                'route'           => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.page.menu.view' => array(
                'route'           => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "view"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|view'
                ),
                'display' => false //only used for breadcrumb generation
            )
        )
    )
);

//add category level
\Mautic\CategoryBundle\Helper\MenuHelper::addCategoryMenuItems(
    $items['mautic.page.menu.root']['children'],
    'page',
    $security
);

return array(
    'priority' => 9,
    'items'    => $items
);