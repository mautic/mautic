<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
$security = $event->getSecurity();

$items    = array();
if ($security->isGranted('user:users:view')) {
    $items['mautic.user.user.menu.index'] = array(
        'route'           => 'mautic_user_index',
        'linkAttributes'  => array(
            'data-toggle'    => 'ajax',
            'data-menu-link' => '#mautic_user_index',
            'id'             => 'mautic_user_index'
        ),
        'extras'          => array(
            'iconClass' => 'fa-users',
            'routeName' => 'mautic_user_index'
        ),
        'children'        => array(
            'mautic.user.user.menu.new'  => array(
                'route'           => 'mautic_user_action',
                'routeParameters' => array('objectAction' => 'new'),
                'extras'          => array(
                    'routeName' => 'mautic_user_action|new'
                ),
                'display'         => false //only used for breadcrumb generation
            ),
            'mautic.user.user.menu.edit' => array(
                'route'           => 'mautic_user_action',
                'routeParameters' => array('objectAction' => 'edit'),
                'extras'          => array(
                    'routeName' => 'mautic_user_action|edit'
                ),
                'display'         => false //only used for breadcrumb generation
            )
        )
    );
}

if ($security->isGranted('user:roles:view')) {
    $items['mautic.user.role.menu.index'] = array(
        'route'           => 'mautic_role_index',
        'extras'          => array(
            'iconClass' => 'fa-lock',
            'routeName' => 'mautic_role_index'
        ),
        'linkAttributes'  => array(
            'data-toggle'    => 'ajax',
            'data-menu-link' => '#mautic_role_index',
            'id'             => 'mautic_role_index'
        ),
        'children'        => array(
            'mautic.user.role.menu.new'  => array(
                'route'           => 'mautic_role_action',
                'routeParameters' => array('objectAction' => 'new'),
                'extras'          => array(
                    'routeName' => 'mautic_role_action|new'
                ),
                'display'         => false //only used for breadcrumb generation
            ),
            'mautic.user.role.menu.edit' => array(
                'route'           => 'mautic_role_action',
                'routeParameters' => array('objectAction' => 'edit'),
                'extras'          => array(
                    'routeName' => 'mautic_role_action|edit'
                ),
                'display'         => false //only used for breadcrumb generation
            )
        )
    );
}

return $items;
