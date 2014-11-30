<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


$items = array();

if ($security->isGranted('user:users:view')) {
    $items['mautic.user.user.menu.index'] = array(
        'route'     => 'mautic_user_index',
        'iconClass' => 'fa-users',
    );
}

if ($security->isGranted('user:roles:view')) {
    $items['mautic.user.role.menu.index'] = array(
        'route'     => 'mautic_role_index',
        'iconClass' => 'fa-lock'
    );
}

return array(
    'items' => $items
);
