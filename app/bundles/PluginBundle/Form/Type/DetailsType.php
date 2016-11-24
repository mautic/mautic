<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        $builder->add('isPublished', 'yesno_button_group');

        $keys          = $options['integration_object']->getRequiredKeyFields();
        $decryptedKeys = $options['integration_object']->decryptApiKeys($options['data']->getApiKeys());
        $formSettings  = $options['integration_object']->getFormDisplaySettings();

        if (!empty($formSettings['hide_keys'])) {
            foreach ($formSettings['hide_keys'] as $key) {
                unset($keys[$key]);
            }
        }

        $builder->add('apiKeys', 'integration_keys', [
            'label'              => false,
            'integration_keys'   => $keys,
            'data'               => $decryptedKeys,
            'integration_object' => $options['integration_object'],
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($keys, $decryptedKeys, $options) {
            $data = $event->getData();
            $form = $event->getForm();

            $form->add('apiKeys', 'integration_keys', [
                'label'              => false,
                'integration_keys'   => $keys,
                'data'               => $decryptedKeys,
                'integration_object' => $options['integration_object'],
                'is_published'       => (int) $data['isPublished'],
            ]);
        });

        if (!empty($formSettings['requires_authorization'])) {
            $disabled = false;
            $label    = ($options['integration_object']->isAuthorized()) ? 'reauthorize' : 'authorize';

            $builder->add('authButton', 'standalone_button', [
                'attr' => [
                    'class'   => 'btn btn-success btn-lg',
                    'onclick' => 'Mautic.initiateIntegrationAuthorization()',
                    'icon'    => 'fa fa-key',

                ],
                'label'    => 'mautic.integration.form.'.$label,
                'disabled' => $disabled,
            ]);
        }

        $features = $options['integration_object']->getSupportedFeatures();
        if (!empty($features)) {
            // Check to see if the integration is a new entry and thus not configured
            $configured      = $options['data']->getId() !== null;
            $enabledFeatures = $options['data']->getSupportedFeatures();
            $data            = ($configured) ? $enabledFeatures : $features;

            $choices = [];
            foreach ($features as $f) {
                $choices[$f] = 'mautic.integration.form.feature.'.$f;
            }

            $builder->add('supportedFeatures', 'choice', [
                'choices'    => $choices,
                'expanded'   => true,
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => true,
                'label'      => 'mautic.integration.form.features',
                'required'   => false,
                'data'       => $data,
            ]);
        }

        $builder->add('featureSettings', 'integration_featuresettings', [
            'label'              => 'mautic.integration.form.feature.settings',
            'required'           => true,
            'data'               => $options['data']->getFeatureSettings(),
            'label_attr'         => ['class' => 'control-label'],
            'integration'        => $options['integration'],
            'integration_object' => $options['integration_object'],
            'lead_fields'        => $options['lead_fields'],
            'company_fields'     => $options['company_fields'],
        ]);

        $builder->add('name', 'hidden', ['data' => $options['integration']]);

        $builder->add('in_auth', 'hidden', ['mapped' => false]);

        $builder->add('buttons', 'form_buttons', [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Mautic\PluginBundle\Entity\Integration',
        ]);

        $resolver->setRequired(['integration', 'integration_object', 'lead_fields', 'company_fields']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_details';
    }
}
