<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('addon:addons:manage'), 'MATCH_ONE')) {
    return array();
}

return array(
    'priority' => 50,
    'items'    => array(
        'mautic.addon.menu.root' => array(
            'id'        => 'mautic_integration_root',
            'iconClass' => 'fa-plus-circle',
            'children'  => array(
                'mautic.addon.menu.index'           => array(
                    'route' => 'mautic_integration_index',
                ),
                'mautic.addon.integration.menu.index' => array(
                    'route' => 'mautic_integration_integration_index'
                ),
            )
        )
    )
);