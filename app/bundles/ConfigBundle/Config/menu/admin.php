<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


if (!$user->isAdmin()) {
    return array();
}


return array(
    'items' => array(
        'mautic.config.config.menu.index' => array(
            'route'           => 'mautic_config_action',
            'routeParameters' => array('objectAction' => 'edit'),
            'iconClass'       => 'fa-cogs',
            'id'              => 'mautic_config_index'
        )
    )
);
