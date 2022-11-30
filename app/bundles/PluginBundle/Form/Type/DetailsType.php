<?php

namespace Mautic\PluginBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DetailsType.
 */
class DetailsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('isPublished', YesNoButtonGroupType::class);

        $formSettings  = $options['integration_object']->getFormDisplaySettings();
        $decryptedKeys = $options['integration_object']->decryptApiKeys($options['data']->getApiKeys());
        $keys          = $options['integration_object']->getRequiredKeyFields();

        if (!empty($formSettings['hide_keys'])) {
            foreach ($formSettings['hide_keys'] as $key) {
                unset($keys[$key]);
            }
        }

        $builder->add(
                'apiKeys',
                KeysType::class,
                [
                    'label'              => false,
                    'integration_keys'   => $keys,
                    'data'               => $decryptedKeys,
                    'integration_object' => $options['integration_object'],
                ]
            );

        $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($keys, $decryptedKeys, $options) {
                    $data = $event->getData();
                    $form = $event->getForm();

                    $form->add(
                        'apiKeys',
                        KeysType::class,
                        [
                            'label'              => false,
                            'integration_keys'   => $keys,
                            'data'               => $decryptedKeys,
                            'integration_object' => $options['integration_object'],
                            'is_published'       => (int) $data['isPublished'],
                        ]
                    );
                }
            );

        if (!empty($formSettings['requires_authorization'])) {
            $disabled = false;
            $label    = ($options['integration_object']->isAuthorized()) ? 'reauthorize' : 'authorize';

            $builder->add(
                    'authButton',
                    StandAloneButtonType::class,
                    [
                        'attr'     => [
                            'class'   => 'btn btn-success btn-lg',
                            'onclick' => 'Mautic.initiateIntegrationAuthorization()',
                            'icon'    => 'fa fa-key',
                        ],
                        'label'    => 'mautic.integration.form.'.$label,
                        'disabled' => $disabled,
                    ]
                );
        }

        $features = $options['integration_object']->getSupportedFeatures();
        $tooltips = $options['integration_object']->getSupportedFeatureTooltips();
        if (!empty($features)) {
            // Check to see if the integration is a new entry and thus not configured
            $configured      = null !== $options['data']->getId();
            $enabledFeatures = $options['data']->getSupportedFeatures();
            $data            = ($configured) ? $enabledFeatures : $features;

            $choices = [];
            foreach ($features as $f) {
                $choices['mautic.integration.form.feature.'.$f] = $f;
            }

            $builder->add(
                'supportedFeatures',
                ChoiceType::class,
                [
                    'choices'     => $choices,
                    'expanded'    => true,
                    'label_attr'  => ['class' => 'control-label'],
                    'multiple'    => true,
                    'label'       => 'mautic.integration.form.features',
                    'required'    => false,
                    'data'        => $data,
                    'choice_attr' => function ($val, $key, $index) use ($tooltips) {
                        if (array_key_exists($val, $tooltips)) {
                            return [
                                'data-toggle' => 'tooltip',
                                'title'       => $tooltips[$val],
                            ];
                        } else {
                            return [];
                        }
                    },
                ]
            );
        }

        $builder->add(
            'featureSettings',
            FeatureSettingsType::class,
            [
                'label'              => 'mautic.integration.form.feature.settings',
                'required'           => true,
                'data'               => $options['data']->getFeatureSettings(),
                'label_attr'         => ['class' => 'control-label'],
                'integration'        => $options['integration'],
                'integration_object' => $options['integration_object'],
                'lead_fields'        => $options['lead_fields'],
                'company_fields'     => $options['company_fields'],
            ]
        );

        $builder->add('name', HiddenType::class, ['data' => $options['integration']]);

        $builder->add('in_auth', HiddenType::class, ['mapped' => false]);

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $options['integration_object']->modifyForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Integration::class,
            ]
        );

        $resolver->setRequired(['integration', 'integration_object', 'lead_fields', 'company_fields']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'integration_details';
    }
}
