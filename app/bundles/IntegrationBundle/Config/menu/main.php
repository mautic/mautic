<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$items = array(
    'mautic.integration.menu.root' => array(
        'linkAttributes' => array(
            'id' => 'mautic_integration_root'
        ),
        'extras'         => array(
            'iconClass' => 'fa-plus-circle'
        ),
        'display'        => ($security->isGranted(array('integration:integrations:view'), 'MATCH_ONE')) ? true : false,
        'children'       => array(
            'mautic.integration.menu.index' => array(
                'route'          => 'mautic_integration_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'         => array(
                    'routeName' => 'mautic_integration_index'
                )
            ),
            'mautic.integration.connector.menu.index' => array(
                'route'          => 'mautic_integration_connector_index',
                'linkAttributes' => array(
                    'data-toggle' => 'ajax'
                ),
                'extras'         => array(
                    'routeName' => 'mautic_integration_connector_index'
                )
            ),
        )
    )
);

return array(
    'priority' => 5,
    'items'    => $items
);

