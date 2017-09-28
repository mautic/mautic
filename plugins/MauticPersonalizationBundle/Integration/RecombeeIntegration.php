<?php
namespace MauticPlugin\MauticPersonalizationBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticPersonalizationBundle\Helper\RecombeeHelper;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Ex;

class RecombeeIntegration extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Recombee';
    }

    public function getIcon()
    {
        return 'plugins/MauticPersonalizationBundle/Assets/img/recombee.png';
    }

    public function getSupportedFeatures()
    {
        return [
        ];
    }

    public function getSupportedFeatureTooltips()
    {
        return [
        //    'tracking_page_enabled' => 'mautic.integration.form.features.tracking_page_enabled.tooltip',
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
            'database'        => 'mautic.plugin.personalization.integration.database',
            'secret_key' =>        'mautic.plugin.personalization.integration.secret_key',
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
//        if ($formArea == 'features') {
//            /* @var FormBuilder $builder */
//            $builder->add(
//                'subdomain_name',
//                TextType::class,
//                [
//                    'label'    => 'mautic.notification.form.subdomain_name.label',
//                    'required' => false,
//                    'attr'     => [
//                        'class' => 'form-control',
//                    ],
//                ]
//            );
//
//            $builder->add(
//                'platforms',
//                ChoiceType::class,
//                [
//                    'choices' => [
//                        'ios'     => 'mautic.integration.form.platforms.ios',
//                        'android' => 'mautic.integration.form.platforms.android',
//                    ],
//                    'attr' => [
//                        'tooltip'      => 'mautic.integration.form.platforms.tooltip',
//                        'data-show-on' => '{"integration_details_supportedFeatures_0":"checked"}',
//                    ],
//                    'expanded'    => true,
//                    'multiple'    => true,
//                    'label'       => 'mautic.integration.form.platforms',
//                    'empty_value' => false,
//                    'required'    => false,
//                ]
//            );
//        }
    }
}
