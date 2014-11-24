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
$items = array(
    'mautic.lead.lead.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'menu_lead_parent'
        ),
        'extras'=> array(
            'iconClass' => 'fa-user'
        ),
        'display' => ($security->isGranted(array('lead:leads:viewown', 'lead:leads:viewother'), 'MATCH_ONE')) ? true : false,
        'children' => array(
            'mautic.lead.lead.menu.index' => array(
                'route'    => 'mautic_lead_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
            ),
            'mautic.lead.lead.menu.new' => array(
                'route'    => 'mautic_lead_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_lead_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.lead.lead.menu.edit' => array(
                'route'           => 'mautic_lead_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_lead_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.lead.lead.menu.view' => array(
                'route'           => 'mautic_lead_action',
                'routeParameters' => array("objectAction"  => "view"),
                'extras'  => array(
                    'routeName' => 'mautic_lead_action|view'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.lead.list.menu.index' => array(
                'route'    => 'mautic_leadlist_index',
                'extras'  => array(
                    'routeName' => 'mautic_leadlist_index'
                ),
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'children' => array(
                    'mautic.lead.list.menu.new' => array(
                        'route'    => 'mautic_leadlist_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_leadlist_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.lead.list.menu.edit' => array(
                        'route'           => 'mautic_leadlist_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_leadlist_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            ),
            'mautic.lead.field.menu.index' => array(
                'route'    => 'mautic_leadfield_index',
                'extras'  => array(
                    'routeName' => 'mautic_leadfield_index'
                ),
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'display' => $security->isGranted('lead:fields:full') ? true : false,
                'children' => array(
                    'mautic.lead.field.menu.new' => array(
                        'route'    => 'mautic_leadfield_action',
                        'routeParameters' => array("objectAction"  => "new"),
                        'extras'  => array(
                            'routeName' => 'mautic_leadfield_action|new'
                        ),
                        'display' => false //only used for breadcrumb generation
                    ),
                    'mautic.lead.field.menu.edit' => array(
                        'route'           => 'mautic_leadfield_action',
                        'routeParameters' => array("objectAction"  => "edit"),
                        'extras'  => array(
                            'routeName' => 'mautic_leadfield_action|edit'
                        ),
                        'display' => false //only used for breadcrumb generation
                    )
                )
            )
        )
    )
);

return array(
    'priority' => 3,
    'items'    => $items
);
