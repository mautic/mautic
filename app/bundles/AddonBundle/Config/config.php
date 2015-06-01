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
            'mautic_integration_auth_callback' => array(
                'path'       => '/addon/integrations/authcallback/{integration}',
                'controller' => 'MauticAddonBundle:Auth:authCallback'
            ),
            'mautic_integration_auth_postauth' => array(
                'path'       => '/addon/integrations/authstatus',
                'controller' => 'MauticAddonBundle:Auth:authStatus'
            ),
            'mautic_addon_integration_index'   => array(
                'path'       => '/addon/integrations',
                'controller' => 'MauticAddonBundle:Integration:index'
            ),
            'mautic_addon_integration_edit'    => array(
                'path'       => '/addon/integrations/edit/{name}',
                'controller' => 'MauticAddonBundle:Integration:edit'
            ),
            'mautic_addon_index'               => array(
                'path'       => '/addon/{page}',
                'controller' => 'MauticAddonBundle:Addon:index'
            ),
            'mautic_addon_action'              => array(
                'path'       => '/addon/{objectAction}/{objectId}',
                'controller' => 'MauticAddonBundle:Addon:execute'
            )
        )
    ),

    'menu'     => array(
        'admin' => array(
            'priority' => 50,
            'items'    => array(
                'mautic.addon.addons' => array(
                    'id'        => 'mautic_addon_root',
                    'iconClass' => 'fa-plus-circle',
                    'access'    => 'addon:addons:manage',
                    'route'     => 'mautic_addon_index'
                )
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.addon.pointbundle.subscriber' => array(
                'class' => 'Mautic\AddonBundle\EventListener\PointSubscriber'
            ),
            'mautic.addon.formbundle.subscriber' => array(
                'class' => 'Mautic\AddonBundle\EventListener\FormSubscriber'
            ),
            'mautic.addon.campaignbundle.subscriber' => array(
                'class' => 'Mautic\AddonBundle\EventListener\CampaignSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.integration.details'  => array(
                'class' => 'Mautic\AddonBundle\Form\Type\DetailsType',
                'alias' => 'integration_details'
            ),
            'mautic.form.type.integration.settings' => array(
                'class'     => 'Mautic\AddonBundle\Form\Type\FeatureSettingsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'integration_featuresettings'
            ),
            'mautic.form.type.integration.fields'   => array(
                'class' => 'Mautic\AddonBundle\Form\Type\FieldsType',
                'alias' => 'integration_fields'
            ),
            'mautic.form.type.integration.keys'     => array(
                'class' => 'Mautic\AddonBundle\Form\Type\KeysType',
                'alias' => 'integration_keys'
            ),
            'mautic.form.type.integration.list'     => array(
                'class'     => 'Mautic\AddonBundle\Form\Type\IntegrationsListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'integration_list'
            ),
            'mautic.form.type.integration.config' => array(
                'class'     => 'Mautic\AddonBundle\Form\Type\IntegrationConfigType',
                'alias'     => 'integration_config'
            )
        ),
        'other'  => array(
            'mautic.helper.integration' => array(
                'class'     => 'Mautic\AddonBundle\Helper\IntegrationHelper',
                'arguments' => 'mautic.factory'
            ),
            'mautic.helper.addon'       => array(
                'class'     => 'Mautic\AddonBundle\Helper\AddonHelper',
                'arguments' => 'mautic.factory'
            )
        )
    )
);