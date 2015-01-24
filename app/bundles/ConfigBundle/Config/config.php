<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes' => array(
        'main' => array(
            'mautic_config_action' => array(
                'path' => '/config/{objectAction}',
                'controller' => 'MauticConfigBundle:Config:execute'
            )
        )
    ),

    'menu' => array(
        'admin' => array(
            'mautic.config.menu.index' => array(
                'route'           => 'mautic_config_action',
                'routeParameters' => array('objectAction' => 'edit'),
                'iconClass'       => 'fa-cogs',
                'id'              => 'mautic_config_index',
                'access'          => 'admin'
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.config.subscriber' => array(
                'class' => 'Mautic\ConfigBundle\EventListener\ConfigSubscriber'
            )
        ),

        'forms' => array(
            'mautic.form.type.config' => array(
                'class' => 'Mautic\ConfigBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias' => 'config'
            )
        )
    )
);