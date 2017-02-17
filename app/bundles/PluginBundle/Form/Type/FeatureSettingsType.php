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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FeatureSettingsType.
 */
class FeatureSettingsType extends AbstractType
{
    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integration_object = $options['integration_object'];

        //add custom feature settings
        $integration_object->appendToForm($builder, $options['data'], 'features');
        $leadFields    = $options['lead_fields'];
        $companyFields = $options['company_fields'];

        $formModifier = function (FormInterface $form, $data, $method = 'get') use ($integration_object, $leadFields, $companyFields) {
            $settings = [
                'silence_exceptions' => false,
                'feature_settings'   => $data,
                'ignore_field_cache' => true,
            ];
            try {
                $fields  = $integration_object->getFormLeadFields($settings);
                $fields  = (isset($fields[0])) ? $fields[0] : $fields;
                $objects = isset($settings['feature_settings']['objects']) ? true : false;

                unset($fields['company']);
                if ($objects and in_array('company', $settings['feature_settings']['objects'])) {
                    $integrationCompanyFields = $integration_object->getFormCompanyFields($settings);

                    if (isset($integrationCompanyFields['company'])) {
                        $integrationCompanyFields = $integrationCompanyFields['company'];
                    }
                }

                if ($objects) {
                    foreach ($settings['feature_settings']['objects'] as $object) {
                        if ('company' == $object || 'Lead' == $object) {
                            continue;
                        }
                        $integrationObjectFields[$object] = $integration_object->getFormFieldsByObject($object);
                    }
                }

                if (!is_array($fields)) {
                    $fields = [];
                }
                $error = '';
            } catch (\Exception $e) {
                $fields = [];
                $error  = $e->getMessage();
            }
            list($specialInstructions, $alertType) = $integration_object->getFormNotes('leadfield_match');
            /**
             * Auto Match Integration Fields with Mautic Fields.
             */
            $flattenLeadFields = [];
            foreach (array_values($leadFields) as $fieldsWithoutGroups) {
                $flattenLeadFields = array_merge($flattenLeadFields, $fieldsWithoutGroups);
            }
            $integrationFields  = array_keys($fields);
            $flattenLeadFields  = array_keys($flattenLeadFields);
            $fieldsIntersection = array_uintersect($integrationFields, $flattenLeadFields, 'strcasecmp');

            $autoMatchedFields = [];
            foreach ($fieldsIntersection as $field) {
                $autoMatchedFields[$field] = strtolower($field);
            }

            $form->add(
                'leadFields',
                'integration_fields',
                [
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'lead_fields'          => $leadFields,
                    'data'                 => isset($data['leadFields']) && !empty($data['leadFields']) ? $data['leadFields'] : $autoMatchedFields,
                    'integration_fields'   => $fields,
                    'special_instructions' => $specialInstructions,
                    'alert_type'           => $alertType,
                ]
            );
            if (!empty($integrationCompanyFields)) {
                $form->add(
                    'companyFields',
                    'integration_company_fields',
                    [
                        'label'                      => 'mautic.integration.comapanyfield_matches',
                        'required'                   => false,
                        'company_fields'             => $companyFields,
                        'integration_company_fields' => $integrationCompanyFields,
                        'special_instructions'       => $specialInstructions,
                        'alert_type'                 => $alertType,
                    ]
                );
            }

            if ($objects) {
                foreach ($settings['feature_settings']['objects'] as $object) {
                    $form->add(
                        $object.'Fields',
                        'integration_object_fields',
                        [
                            'label'                => $object,
                            'required'             => false,
                            'lead_fields'          => $leadFields,
                            'integration_fields'   => $integrationObjectFields,
                            'special_instructions' => $specialInstructions,
                            'alert_type'           => $alertType,
                        ]
                    );
                }
            }
            if ($method == 'get' && $error) {
                $form->addError(new FormError($error));
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data, 'post');
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['integration', 'integration_object', 'lead_fields', 'company_fields']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_featuresettings';
    }
}
