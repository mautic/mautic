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
     * @return string
     */
    public function getIcon()
    {
        return 'plugins//MauticMessengerBundle/Assets/img/facebook-messenger.svg';
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
     * @param $section
     *
     * @return string|array
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'template'   => 'MauticMessengerBundle:Integration:messenger.html.php',
                'parameters' => [
                ],
            ];
        }

        return parent::getFormNotes($section);
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
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {

            /* @var FormBuilder $builder */

            $builder->add(
                'messenger_app_id',
                TextType::class,
                [
                    'label'    => 'mautic.integration.messenger.app.id',
                    'required' => false,
                    'attr'     => [
                        'tooltip' => 'mautic.integration.messenger.app.id.tooltip',
                        'class'   => 'form-control',
                    ],
                ]
            );
            $builder->add(
                'messenger_page_id',
                TextType::class,
                [
                    'label'    => 'mautic.integration.messenger.page.id',
                    'required' => false,
                    'attr'     => [
                        'tooltip' => 'mautic.integration.messenger.page.id.tooltip',
                        'class'   => 'form-control',
                    ],
                ]
            );

            $builder->add(
                'messenger_callback_verify_token',
                TextType::class,
                [
                    'label'    => 'mautic.integration.messenger.verify.token',
                    'required' => false,
                    'attr'     => [
                        'tooltip' => 'mautic.integration.messenger.verify.token.tooltip',
                        'class'   => 'form-control',
                    ],
                ]
            );

            $builder->add(
                'messenger_page_access_token',
                TextType::class,
                [
                    'label'    => 'mautic.integration.messenger.page.access.token',
                    'required' => false,
                    'attr'     => [
                        'tooltip' => 'mautic.integration.messenger.page.access.token.tooltip',
                        'class'   => 'form-control',
                    ],
                ]
            );
        }
        if ($formArea == 'features') {
            $builder->add(
                'messenger_domains',
                'sortablelist',
                [
                    'label'           => 'mautic.lead.field.form.properties.select',
                    'attr'            => ['data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}'],
                    'option_required' => false,
                    'with_labels'     => false,
                ]
            );
        }
    }
}
