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
    'mautic.api.client.menu.index' => array(
        'route'    => 'mautic_client_index',
        'uri'      => 'javascript: void(0)',
        'linkAttributes' => array(
            'onclick' => $this->mauticSecurity->isGranted('api:clients:view') ?
                    'return Mautic.loadContent(\''
                    . $this->container->get('router')->generate('mautic_client_index')
                    . '\', \'#mautic_client_index\', true);'
                : 'Mautic.toggleSubMenu(\'#mautic_client_index\');',
            'id'      => 'mautic_client_index'
        ),
        'labelAttributes' => array(
            'class'   => 'nav-item-name'
        ),
        'extras'    => array(
            'iconClass' => 'fa-puzzle-piece fa-lg',
            'routeName' => 'mautic_client_index'
        ),
        'display'   => ($this->mauticSecurity->isGranted('api:clients:view')) ? true : false,
        'children' => array(
            'mautic.api.client.menu.new' => array(
                'route'    => 'mautic_client_action',
                'routeParameters' => array("objectAction"  => "new"),
                'extras'  => array(
                    'routeName' => 'mautic_client_action|new'
                ),
                'display' => false //only used for breadcrumb generation
            ),
            'mautic.api.client.menu.edit' => array(
                'route'           => 'mautic_client_action',
                'routeParameters' => array("objectAction"  => "edit"),
                'extras'  => array(
                    'routeName' => 'mautic_client_action|edit'
                ),
                'display' => false //only used for breadcrumb generation
            )
        )
    )
);

return $items;
