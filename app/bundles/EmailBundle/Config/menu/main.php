<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.email.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_email_root'
        ),
        'extras'=> array(
            'iconClass' => 'fa-send'
        ),
        'display' => ($security->isGranted(array('email:emails:viewown', 'email:emails:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.email.menu.index' => array(
                'route'    => 'mautic_email_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'=> array(
                    'routeName' => 'mautic_email_index'
                ),
            ),
            'mautic.email.menu.new' => array(
                'route'    => 'mautic_email_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_email_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.email.menu.edit' => array(
                'route'           => 'mautic_email_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_email_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.email.menu.view' => array(
                'route'           => 'mautic_email_action',
                'routeParameters' => array("objectAction"  => "view"),
                'extras'  => array(
                    'routeName' => 'mautic_email_action|view'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.category.menu.index' => array(
                'route'    => 'mautic_category_index',
                'routeParameters' => array("bundle"  => "email"),
                'extras'  => array(
                    'routeName' => 'mautic_category_index'
                ),
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'display' => $security->isGranted('email:categories:view') ? true : false,
                'children' => array(
                    'mautic.category.menu.new' => array(
                        'route'    => 'mautic_category_action',
                        'routeParameters' => array(
                            "objectAction"  => "new",
                            "bundle"        => "email"
                        ),
                        'extras'  => array(
                            'routeName' => 'mautic_category_action|email|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.category.menu.edit' => array(
                        'route'           => 'mautic_category_action',
                        'routeParameters' => array(
                            "objectAction"  => "edit",
                            "bundle"        => "email"
                        ),
                        'extras'  => array(
                            'routeName' => 'mautic_category_action|email|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

return array(
    'priority' => 6,
    'items'    => $items
);