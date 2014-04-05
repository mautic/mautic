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
        'uri'      => 'javascript: void(0)',
        'linkAttributes' => array(
            'onclick' =>
                'return Mautic.loadMauticContent(\''
                . $this->container->get('router')->generate('mautic_user_index')
                . '\', \'#mautic_user_index\', true);',
            'id'      => 'mautic_user_index'
        ),
        'labelAttributes' => array(
            'class'   => 'nav-item-name'
        ),
        'extras'=> array(
            'iconClass' => 'fa-users fa-lg',
            'routeName' => 'mautic_user_index'
        ),
        'children' => array(
            'mautic.user.role.menu.index' => array(
                'route'         => 'mautic_role_index',
                'uri'           => 'javascript: void(0)',
                'linkAttributes' => array(
                    'onclick' =>
                        'return Mautic.loadMauticContent(\''
                        . $this->container->get('router')->generate('mautic_role_index')
                        . '\', \'#mautic_role_index\', true);',
                    'id'      => 'mautic_role_index'
                ),
                'labelAttributes' => array(
                    'class'   => 'nav-item-name'
                ),
                'extras'          => array(
                    'iconClass' => 'fa-lock',
                    'routeName' => 'mautic_role_index'
                ),
                'children'        => array(
                    'mautic.user.role.menu.new' => array(
                        'route'    => 'mautic_role_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_role_action|new'
                        ),
                        'display' => false
                    ),
                    'mautic.user.role.menu.edit' => array(
                        'route'           => 'mautic_role_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_role_action|edit'
                        ),
                        'display' => false
                    )
                )
            ),
            'mautic.user.user.menu.new' => array(
                'route'    => 'mautic_user_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_user_action|new'
                ),
                'display' => false
            ),
            'mautic.user.user.menu.edit' => array(
                'route'           => 'mautic_user_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_user_action|edit'
                ),
                'display' => false
            )
        )
    )
);
