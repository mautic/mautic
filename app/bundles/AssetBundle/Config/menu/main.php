<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('asset:assets:viewown', 'asset:assets:viewother'), 'MATCH_ONE')) {
    return array();
}

return array(
    'priority' => 10,
    'items'    => array(
        'mautic.asset.asset.menu.root' => array(
            'id'        => 'mautic_asset_root',
            'iconClass' => 'fa-folder-open-o',
            'children'  => array(
                'mautic.asset.asset.menu.index' => array(
                    'route' => 'mautic_asset_index',
                ),
                'mautic.category.menu.index'    => array(
                    'route'           => 'mautic_category_index',
                    'routeParameters' => array('bundle' => 'asset')
                )
            )
        )
    )
);