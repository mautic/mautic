<?php

namespace Mautic\PluginBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait FieldsTypeTrait
{
    /**
     * @param string $fieldObject
     */
    protected function buildFormFields(
        FormBuilderInterface $builder,
        array $options,
        array $integrationFields,
        array $mauticFields,
        $fieldObject,
        $limit,
        $start
    ) {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $integrationFields, $mauticFields, $fieldObject, $limit, $start): void {
                $form           = $event->getForm();
                $index          = 0;
                $choices        = [];
                $requiredFields = [];
                $optionalFields = [];
                $group          = [];
                $fieldData      = $event->getData();

                foreach ($mauticFields as $key => $value) {
                    if (is_array($value)) {
                        $mauticFields[$key] = array_flip($value);
                    }
                }

                // First loop to build options
                foreach ($integrationFields as $field => $details) {
                    $groupName = '0default';
                    if (is_array($details)) {
                        if (isset($details['group'])) {
                            if (!isset($choices[$details['group']])) {
                                $choices[$details['group']] = [];
                            }
                            $label           = $details['optionLabel'] ?? $details['label'];
                            $group[$field]   = $groupName = $details['group'];
                            $choices[$field] = $label;
                        } else {
                            $choices[$field] = $details['label'];
                        }
                    } else {
                        $choices[$field] = $details;
                    }

                    if (!isset($requiredFields[$groupName])) {
                        $requiredFields[$groupName] = [];
                        $optionalFields[$groupName] = [];
                    }

                    if (is_array($details) && (!empty($details['required']) || 'Email' == $choices[$field])) {
                        $requiredFields[$groupName][$field] = $details;
                    } else {
                        $optionalFields[$groupName][$field] = $details;
                    }
                }

                // Order the fields by label
                ksort($requiredFields, SORT_NATURAL);
                ksort($optionalFields, SORT_NATURAL);

                $sortFieldsFunction = function ($a, $b): int {
                    if (is_array($a)) {
                        $aLabel = $a['optionLabel'] ?? $a['label'];
                    } else {
                        $aLabel = $a;
                    }

                    if (is_array($b)) {
                        $bLabel = $b['optionLabel'] ?? $b['label'];
                    } else {
                        $bLabel = $b;
                    }

                    return strnatcasecmp($aLabel, $bLabel);
                };

                $fields = [];
                foreach ($requiredFields as $groupedFields) {
                    uasort($groupedFields, $sortFieldsFunction);

                    $fields = array_merge($fields, $groupedFields);
                }
                foreach ($optionalFields as $groupedFields) {
                    uasort($groupedFields, $sortFieldsFunction);

                    $fields = array_merge($fields, $groupedFields);
                }

                // Ensure that fields aren't hidden
                if ($start > count($fields) || 0 == $options['page']) {
                    $start = 0;
                }

                $paginatedFields = array_slice($fields, $start, $limit);
                $fieldsName      = 'leadFields';
                if ($fieldObject) {
                    $fieldsName = $fieldObject.'Fields';
                }
                if (isset($fieldData[$fieldsName])) {
                    $fieldData[$fieldsName] = $options['integration_object']->formatMatchedFields($fieldData[$fieldsName]);
                }

                foreach ($paginatedFields as $field => $details) {
                    $matched  = isset($fieldData[$fieldsName][$field]);
                    $required = (int) (!empty($integrationFields[$field]['required']) || 'Email' == $choices[$field]);
                    ++$index;
                    $form->add(
                        'label_'.$index,
                        TextType::class,
                        [
                            'label' => false,
                            'data'  => $choices[$field],
                            'attr'  => [
                                'class'         => 'form-control integration-fields',
                                'data-required' => $required,
                                'data-label'    => $choices[$field],
                                'placeholder'   => $group[$field] ?? '',
                                'readonly'      => true,
                            ],
                            'by_reference' => true,
                            'mapped'       => false,
                        ]
                    );
                    if (isset($options['enable_data_priority']) and $options['enable_data_priority']) {
                        $updateName = 'update_mautic';

                        if ($fieldObject) {
                            $updateName .= '_'.$fieldObject;
                        }

                        $forceDirection = false;
                        $disabled       = (isset($fieldData[$fieldsName][$field])) ? $options['integration_object']->isCompoundMauticField($fieldData[$fieldsName][$field]) : false;
                        $data           = isset($fieldData[$updateName][$field]) ? (int) $fieldData[$updateName][$field] : 1;

                        // Force to use just one way for certainly fields
                        if (isset($fields[$field]['update_mautic'])) {
                            $data           = (bool) $fields[$field]['update_mautic'];
                            $disabled       = true;
                            $forceDirection = true;
                        }

                        $form->add(
                            $updateName.$index,
                            ButtonGroupType::class,
                            [
                                'choices' => [
                                    '<btn class="btn-nospin fa fa-arrow-circle-left"></btn>'  => 0,
                                    '<btn class="btn-nospin fa fa-arrow-circle-right"></btn>' => 1,
                                ],
                                'label'             => false,
                                'data'              => $data,
                                'placeholder'       => false,
                                'attr'              => [
                                    'data-toggle'   => 'tooltip',
                                    'title'         => 'mautic.plugin.direction.data.update',
                                    'disabled'      => $disabled,
                                    'forceDirection'=> $forceDirection,
                                ],
                            ]
                        );
                    }

                    if (!$fieldObject) {
                        $mauticFields['mautic.lead.report.contact_id']                        = 'mauticContactId';
                        $mauticFields['mautic.plugin.integration.contact.timeline.link']      = 'mauticContactTimelineLink';
                        $mauticFields['mautic.plugin.integration.contact.donotcontact.email'] = 'mauticContactIsContactableByEmail';
                    }

                    $form->add(
                        'm_'.$index,
                        ChoiceType::class,
                        [
                            'choices'    => $mauticFields,
                            'label'      => false,
                            'data'       => $matched && isset($fieldData[$fieldsName][$field]) ? $fieldData[$fieldsName][$field] : '',
                            'label_attr' => ['class' => 'control-label'],
                            'attr'       => [
                                'class'            => 'field-selector',
                                'data-placeholder' => ' ',
                                'data-required'    => $required,
                                'data-value'       => $matched && isset($fieldData[$fieldsName][$field]) ? $fieldData[$fieldsName][$field] : '',
                                'data-choices'     => $mauticFields,
                            ],
                        ]
                    );
                    $form->add(
                        'i_'.$index,
                        HiddenType::class,
                        [
                            'data' => $field,
                            'attr' => [
                                'data-required' => $required,
                                'data-value'    => $field,
                            ],
                        ]
                    );
                    $form->add(
                        $field,
                        HiddenType::class,
                        [
                            'data' => $index,
                            'attr' => [
                                'data-required' => $required,
                                'data-value'    => $index,
                            ],
                        ]
                    );
                }
            }
        );
    }

    protected function configureFieldOptions(OptionsResolver $resolver, $object)
    {
        $resolver->setRequired(['integration_fields', 'mautic_fields', 'integration', 'integration_object', 'page']);
        $resolver->setDefined([('lead' === $object) ? 'update_mautic' : 'update_mautic_company']);
        $resolver->setDefaults(
            [
                'special_instructions' => function (Options $options) {
                    [$specialInstructions, $alertType] = $options['integration_object']->getFormNotes('leadfield_match');

                    return $specialInstructions;
                },
                'alert_type' => function (Options $options) {
                    [$specialInstructions, $alertType] = $options['integration_object']->getFormNotes('leadfield_match');

                    return $alertType;
                },
                'allow_extra_fields'   => true,
                'enable_data_priority' => false,
                'totalFields'          => fn (Options $options): int => count($options['integration_fields']),
                'fixedPageNum'         => fn (Options $options): float => ceil($options['totalFields'] / $options['limit']),
                'limit'                => 10,
                'start'                => fn (Options $options): int => (1 === (int) $options['page']) ? 0 : ((int) $options['page'] - 1) * (int) $options['limit'],
            ]
        );
    }

    protected function buildFieldView(FormView $view, array $options)
    {
        $view->vars['specialInstructions'] = $options['special_instructions'];
        $view->vars['alertType']           = $options['alert_type'];
        $view->vars['integration']         = $options['integration'];
        $view->vars['totalFields']         = $options['totalFields'];
        $view->vars['page']                = $options['page'];
        $view->vars['fixedPageNum']        = $options['fixedPageNum'];
    }
}
