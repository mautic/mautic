<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

/**
 * Class FacebookIntegration.
 */
class MessengerIntegration extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Messenger';
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
     */
    public function getSupportedFeatures()
    {
        return [
            'checkbox_plugin',
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'messenger_page_access_token'  => 'mautic.integration.messenger.page.access.token',
            'messenger_callback_verify_token' => 'mautic.integration.messenger.verify.token',
        ];
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
        }
    }



}
