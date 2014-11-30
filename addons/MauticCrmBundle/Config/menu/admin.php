<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted('addon:addons:manage')) {
    return array();
}

return array(
    'items' => array(
        'mautic.crm.menu.config' => array(
            'route'     => 'mautic_crm_index',
            'iconClass' => 'fa-cloud'
        )
    )
);
