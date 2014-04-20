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
$items = array(
    'mautic.user.user.menu.index' => array(
        'route'    => 'mautic_user_index',
        'extras'    => array(
            'routeName' => 'mautic_user_index'
        ),
        'display'   => false,
        'children' => array(
            'mautic.user.user.menu.new' => array(
                'route'    => 'mautic_user_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_user_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.user.user.menu.edit' => array(
                'route'           => 'mautic_user_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_user_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            )
        )
    ),
    'mautic.user.role.menu.index' => array(
        'route'         => 'mautic_role_index',
        'extras'          => array(
            'routeName' => 'mautic_role_index'
        ),
        'display'         => false,
        'children'        => array(
            'mautic.user.role.menu.new' => array(
                'route'    => 'mautic_role_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_role_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.user.role.menu.edit' => array(
                'route'           => 'mautic_role_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_role_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            )
        )
    ),
    'mautic.user.account.menu.index' => array(
        'route'    => 'mautic_user_account',
        'extras'  => array(
            'routeName' => 'mautic_user_account'
        ),
        'display'  => false //only used for breadcrumb generation
    )
);

return $items;
