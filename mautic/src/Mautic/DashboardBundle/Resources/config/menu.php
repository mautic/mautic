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
    'mautic.dashboard.menu.index' => array(
        'name'    => 'mautic.dashboard.menu.index',
        'route'    => 'mautic_dashboard_index',
        'uri'      => 'javascript: void(0)',
        'linkAttributes' => array(
            'onclick' =>
                'return Mautic.loadMauticContent(\''
                . $this->container->get('router')->generate('mautic_dashboard_index')
                . '\', \'#mautic_dashboard_index\');',
            'id'      => 'mautic_dashboard_index'
        ),
        'labelAttributes' => array(
            'class'   => 'nav-item-name'
        ),
        'extras'=> array(
            'iconClass' => 'fa-th-large fa-lg',
            'routeName' => 'mautic_dashboard_index'
        )
    )
);