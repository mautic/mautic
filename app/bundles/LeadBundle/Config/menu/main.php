<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$security->isGranted(array('lead:leads:viewown', 'lead:leads:viewother'), 'MATCH_ONE')) {
    return array();
}

$items = array(
    'mautic.lead.lead.menu.root' => array(
        'id'        => 'menu_lead_parent',
        'iconClass' => 'fa-user',
        'children'  => array(
            'mautic.lead.lead.menu.index' => array(
                'route' => 'mautic_lead_index',
            ),
            'mautic.lead.list.menu.index' => array(
                'route' => 'mautic_leadlist_index',
            )
        )
    )
);

if ($security->isGranted('lead:fields:full')) {
    $items['mautic.lead.lead.menu.root']['children']['mautic.lead.field.menu.index'] = array(
        'route' => 'mautic_leadfield_index'
    );
}

return array(
    'priority' => 3,
    'items'    => $items
);
