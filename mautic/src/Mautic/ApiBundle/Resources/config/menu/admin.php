<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
$items = array(
    'name' => array(
        'label'    => '',
        'route'    => '',
        'uri'      => '',
        'attributes' => array(),
        'labelAttributes' => array(),
        'linkAttributes' => array(),
        'childrenAttributes' => array(),
        'extras' => array(),
        'display' => true,
        'displayChildren' => true,
        'children' => array()
    )
);
 */
$security = $event->getMauticSecurity();
$items = array();

if ($security->isGranted('api:clients:view')) {
    $items['mautic.api.client.menu.index'] = array(
        'route'           => 'mautic_client_index',
        'linkAttributes'  => array(
            'data-toggle'    => 'ajax',
            'data-menu-link' => '#mautic_client_index',
            'id'             => 'mautic_client_index'
        ),
        'labelAttributes' => array(
            'class' => 'nav-item-name'
        ),
        'extras'          => array(
            'iconClass' => 'fa-puzzle-piece',
            'routeName' => 'mautic_lead_index'
        ),
        'children'        => array(
            'mautic.api.client.menu.new'  => array(
                'route'           => 'mautic_client_action',
                'routeParameters' => array("objectAction" => "new"),
                'extras'          => array(
                    'routeName' => 'mautic_client_action|new'
                ),
                'display'         => false //only used for breadcrumb generation
            ),
            'mautic.api.client.menu.edit' => array(
                'route'           => 'mautic_client_action',
                'routeParameters' => array("objectAction" => "edit"),
                'extras'          => array(
                    'routeName' => 'mautic_client_action|edit'
                ),
                'display'         => false //only used for breadcrumb generation
            )
        )
    );
}

return $items;
