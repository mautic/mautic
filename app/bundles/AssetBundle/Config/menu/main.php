<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.asset.asset.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_asset_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-folder-open-o'
        ),
        'display' => ($security->isGranted(array('asset:assets:viewown', 'asset:assets:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.asset.asset.menu.index' => array(
                'route'    => 'mautic_asset_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_asset_index'
                ),
            ),
            'mautic.asset.asset.menu.new' => array(
                'route'    => 'mautic_asset_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_asset_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.asset.asset.menu.edit' => array(
                'route'           => 'mautic_asset_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_asset_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.asset.asset.menu.view' => array(
                'route'           => 'mautic_asset_action',
                'routeParameters' => array("objectAction"  => "view"),
                'extras'  => array(
                    'routeName' => 'mautic_asset_action|view'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.category.menu.index' => array(
                'route'    => 'mautic_category_index',
                'routeParameters' => array("bundle"  => "asset"),
                'extras'  => array(
                    'routeName' => 'mautic_category_index'
                ),
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'display' => $security->isGranted('asset:categories:view') ? true : false,
                'children' => array(
                    'mautic.category.menu.new' => array(
                        'route'    => 'mautic_category_action',
                        'routeParameters' => array(
                            "objectAction"  => "new",
                            "bundle"        => "asset"
                        ),
                        'extras'  => array(
                            'routeName' => 'mautic_category_action|asset|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.category.menu.edit' => array(
                        'route'           => 'mautic_category_action',
                        'routeParameters' => array(
                            "objectAction"  => "edit",
                            "bundle"        => "asset"
                        ),
                        'extras'  => array(
                            'routeName' => 'mautic_category_action|asset|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

return array(
    'priority' => 10,
    'items'    => $items
);
