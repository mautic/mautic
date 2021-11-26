<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

/**
 * Class OneSignalIntegration.
 */
class OneSignalIntegration extends AbstractIntegration
{
    protected bool $coreIntegration = true;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'OneSignal';
    }

    public function getIcon()
    {
        return 'app/bundles/NotificationBundle/Assets/img/OneSignal.png';
    }

    public function getSupportedFeatures()
    {
        return [
            'mobile',
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
            'app_id'        => 'mautic.notification.config.form.notification.app_id',
            'safari_web_id' => 'mautic.notification.config.form.notification.safari_web_id',
            'rest_api_key'  => 'mautic.notification.config.form.notification.rest_api_key',
            'gcm_sender_id' => 'mautic.notification.config.form.notification.gcm_sender_id',
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
        if ('features' == $formArea) {
            /* @var FormBuilder $builder */
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
                        'mautic.integration.form.platforms.ios'     => 'ios',
                        'mautic.integration.form.platforms.android' => 'android',
                    ],
                    'attr'              => [
                        'tooltip'      => 'mautic.integration.form.platforms.tooltip',
                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integration.form.platforms',
                    'placeholder' => false,
                    'required'    => false,
                ]
            );
        }
    }
}
