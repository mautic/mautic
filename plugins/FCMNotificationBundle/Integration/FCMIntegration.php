<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

/**
 * Class FCMIntegration.
 */
class FCMIntegration extends AbstractIntegration
{
    /**
     * @var bool
     */
    protected $coreIntegration = false;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'FCM';
    }

     /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'Firebase Cloud Messaging';
    }

    public function getIcon()
    {
        return 'plugins/FCMNotificationBundle/Assets/img/fcm_logo.png';
    }

    public function getSupportedFeatures()
    {
        return [
            //'mobile',
            'landing_page_enabled',
            'welcome_notification_enabled',
            'tracking_page_enabled',
        ];
    }

    public function getSupportedFeatureTooltips()
    {
        return [
            'landing_page_enabled'  => 'mautic.integration.form.features.landing_page_enabled.tooltip',
            'tracking_page_enabled' => 'mautic.integration.form.features.tracking_page_enabled.tooltip',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'apiKey'        => 'mautic.plugin.fcmnotification.config.form.notification.apikey',            
            'projectId'  => 'mautic.plugin.fcmnotification.config.form.notification.projectid',
            'messagingSenderId' => 'mautic.plugin.fcmnotification.config.form.notification.messagingsenderid',
        ];
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            /* @var FormBuilder $builder */
            /*                    
            $builder->add(
                'subdomain_name',
                TextType::class,
                [
                    'label'    => 'mautic.notification.form.subdomain_name.label',
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                    ],
                ]
            );

            $builder->add(
                'platforms',
                ChoiceType::class,
                [
                    'choices' => [
                        'ios'     => 'mautic.integration.form.platforms.ios',
                        'android' => 'mautic.integration.form.platforms.android',
                    ],
                    'attr' => [
                        'tooltip'      => 'mautic.integration.form.platforms.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integration.form.platforms',
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
            */
        }
    }
}
