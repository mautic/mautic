<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.page.page.menu.index' => array(
        'route'    => 'mautic_page_index',
        'linkAttributes' => array(
            'data-toggle' => 'ajax'
        ),
        'extras'=> array(
            'iconClass' => 'fa-file-text-o',
            'routeName' => 'mautic_page_index'
        ),
        'display' => ($security->isGranted(array('page:pages:viewown', 'page:pages:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.page.page.menu.new' => array(
                'route'    => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.page.page.menu.edit' => array(
                'route'           => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.page.page.menu.view' => array(
                'route'           => 'mautic_page_action',
                'routeParameters' => array("objectAction"  => "view"),
                'extras'  => array(
                    'routeName' => 'mautic_page_action|view'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.page.category.menu.index' => array(
                'route'    => 'mautic_pagecategory_index',
                'extras'  => array(
                    'iconClass' => 'fa-caret-right',
                    'routeName' => 'mautic_pagecategory_index'
                ),
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'display' => $security->isGranted('page:categories:view') ? true : false,
                'children' => array(
                    'mautic.page.category.menu.new' => array(
                        'route'    => 'mautic_pagecategory_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_pagecategory_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.page.category.menu.edit' => array(
                        'route'           => 'mautic_pagecategory_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_pagecategory_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

return $items;
