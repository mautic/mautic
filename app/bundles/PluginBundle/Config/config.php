<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main' => array(
            // @deprecated 1.1.4 to be removed in 2.0
            'mautic_integration_auth_callback_bc_secure' => array(
                'path'       => '/addon/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback'
            ),
            'mautic_integration_auth_callback_secure'    => array(
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback'
            ),
            'mautic_integration_auth_postauth_secure'    => array(
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus'
            ),
            'mautic_plugin_index'                 => array(
                'path'       => '/plugins',
                'controller' => 'MauticPluginBundle:Plugin:index'
            ),
            'mautic_plugin_config'                => array(
                'path'       => '/plugins/config/{name}',
                'controller' => 'MauticPluginBundle:Plugin:config'
            ),
            'mautic_plugin_info'                  => array(
                'path'       => '/plugins/info/{name}',
                'controller' => 'MauticPluginBundle:Plugin:info'
            ),
            'mautic_plugin_reload'                => array(
                'path'       => '/plugins/reload',
                'controller' => 'MauticPluginBundle:Plugin:reload'
            )
        ),
        'public' => array(
            'mautic_integration_auth_user'        => array(
                'path'       => '/plugins/integrations/authuser/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authUser'
            ),
            'mautic_integration_auth_callback_bc' => array(
                'path'       => '/addon/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback'
            ),
            'mautic_integration_auth_callback'    => array(
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback'
            ),
            'mautic_integration_auth_postauth'    => array(
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus'
            ),
        )
    ),
    'menu'     => array(
        'admin' => array(
            'priority' => 50,
            'items'    => array(
                'mautic.plugin.plugins' => array(
                    'id'        => 'mautic_plugin_root',
                    'iconClass' => 'fa-plus-circle',
                    'access'    => 'plugin:plugins:manage',
                    'route'     => 'mautic_plugin_index'
                )
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.plugin.pointbundle.subscriber' => array(
                'class' => 'Mautic\PluginBundle\EventListener\PointSubscriber'
            ),
            'mautic.plugin.formbundle.subscriber' => array(
                'class' => 'Mautic\PluginBundle\EventListener\FormSubscriber'
            ),
            'mautic.plugin.campaignbundle.subscriber' => array(
                'class' => 'Mautic\PluginBundle\EventListener\CampaignSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.integration.details'  => array(
                'class' => 'Mautic\PluginBundle\Form\Type\DetailsType',
                'alias' => 'integration_details'
            ),
            'mautic.form.type.integration.settings' => array(
                'class'     => 'Mautic\PluginBundle\Form\Type\FeatureSettingsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'integration_featuresettings'
            ),
            'mautic.form.type.integration.fields'   => array(
                'class' => 'Mautic\PluginBundle\Form\Type\FieldsType',
                'alias' => 'integration_fields'
            ),
            'mautic.form.type.integration.keys'     => array(
                'class' => 'Mautic\PluginBundle\Form\Type\KeysType',
                'alias' => 'integration_keys'
            ),
            'mautic.form.type.integration.list'     => array(
                'class'     => 'Mautic\PluginBundle\Form\Type\IntegrationsListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'integration_list'
            ),
            'mautic.form.type.integration.config' => array(
                'class'     => 'Mautic\PluginBundle\Form\Type\IntegrationConfigType',
                'alias'     => 'integration_config'
            )
        ),
        'other'  => array(
            'mautic.helper.integration' => array(
                'class'     => 'Mautic\PluginBundle\Helper\IntegrationHelper',
                'arguments' => 'mautic.factory'
            )
        )
    )
);