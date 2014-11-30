<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted('api:clients:view')) {
    return array();
}

return array(
    'items' => array(
        'mautic.api.client.menu.index' => array(
            'route'     => 'mautic_client_index',
            'iconClass' => 'fa-puzzle-piece'
        )
    )
);
