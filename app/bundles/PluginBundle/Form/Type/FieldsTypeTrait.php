<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

trait FieldsTypeTrait
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $integrationFields
     * @param array                $mauticFields
     * @param string               $fieldObject
     */
    protected function buildFormFields(
        FormBuilderInterface $builder,
        array $options,
        array $integrationFields,
        array $mauticFields,
        $fieldObject,
        $limit,
        $start,
        TranslatorInterface $translator
    ) {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $integrationFields, $mauticFields, $fieldObject, $limit, $start, $translator) {
                $form = $event->getForm();
                $index = 0;
                $choices = [];
                $requiredFields = [];
                $optionalFields = [];
                $group = [];
                $fieldData = $event->getData();
                // First loop to build options
                foreach ($integrationFields as $field => $details) {
                    $groupName = '0default';
                    if (is_array($details)) {
                        if (isset($details['group'])) {
                            if (!isset($choices[$details['group']])) {
                                $choices[$details['group']] = [];
                            }
                            $label = (isset($details['optionLabel'])) ? $details['optionLabel'] : $details['label'];
                            $group[$field] = $groupName = $details['group'];
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

                    if (is_array($details) && (!empty($details['required']) || $choices[$field] == 'Email')) {
                        $requiredFields[$groupName][$field] = $details;
                    } else {
                        $optionalFields[$groupName][$field] = $details;
                    }
                }

                // Order the fields by label
                ksort($requiredFields, SORT_NATURAL);
                ksort($optionalFields, SORT_NATURAL);

                $sortFieldsFunction = function ($a, $b) {
                    if (is_array($a)) {
                        $aLabel = (isset($a['optionLabel'])) ? $a['optionLabel'] : $a['label'];
                    } else {
                        $aLabel = $a;
                    }

                    if (is_array($b)) {
                        $bLabel = (isset($b['optionLabel'])) ? $b['optionLabel'] : $b['label'];
                    } else {
                        $bLabel = $b;
                    }

                    return strnatcasecmp($aLabel, $bLabel);
                };

                $fields = [];
                foreach ($requiredFields as $groupName => $groupedFields) {
                    uasort($groupedFields, $sortFieldsFunction);

                    $fields = array_merge($fields, $groupedFields);
                }
                foreach ($optionalFields as $groupName => $groupedFields) {
                    uasort($groupedFields, $sortFieldsFunction);

                    $fields = array_merge($fields, $groupedFields);
                }

                // Ensure that fields aren't hidden
                if ($start > count($fields) || $options['page'] == 0) {
                    $start = 0;
                }

                $paginatedFields = array_slice($fields, $start, $limit);
                $fieldsName = 'leadFields';
                if ($fieldObject) {
                    $fieldsName = $fieldObject.'Fields';
                }
                if (isset($fieldData[$fieldsName])) {
                    $fieldData[$fieldsName] = $options['integration_object']->formatMatchedFields($fieldData[$fieldsName]);
                }

                foreach ($paginatedFields as $field => $details) {
                    $matched = isset($fieldData[$fieldsName][$field]);
                    $required = (int) (!empty($integrationFields[$field]['required']) || $choices[$field] == 'Email');
                    ++$index;
                    $form->add(
                        'label_'.$index,
                        'text',
                        [
                            'label' => false,
                            'data'  => $choices[$field],
                            'attr'  => [
                                'class'         => 'form-control integration-fields',
                                'data-required' => $required,
                                'data-label'    => $choices[$field],
                                'placeholder'   => isset($group[$field]) ? $group[$field] : '',
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
                        $form->add(
                            $updateName.$index,
                            'button_group',
                            [
                                'choices' => [
                                    '<btn class="btn-nospin fa fa-arrow-circle-left"></btn>',
                                    '<btn class="btn-nospin fa fa-arrow-circle-right"></btn>',
                                ],
                                'label'       => false,
                                'data'        => isset($fieldData[$updateName][$field]) ? (int) $fieldData[$updateName][$field] : 1,
                                'empty_value' => false,
                                'attr'        => [
                                    'data-toggle' => 'tooltip',
                                    'title'       => 'mautic.plugin.direction.data.update',
                                ],
                            ]
                        );
                    }
                    if (!$fieldObject) {
                        $contactLink['mauticContactTimelineLink'] = $this->translator->trans('mautic.plugin.integration.contact.timeline.link');
                        $isContactable['mauticContactIsContactable'] = $this->translator->trans('mautic.plugin.integration.contact.donotcontact');
                        $mauticFields = array_merge($mauticFields, $contactLink, $isContactable);
                    }

                    $form->add(
                        'm_'.$index,
                        'choice',
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

    /**
     * @param OptionsResolver $resolver
     * @param                 $object
     */
    protected function configureFieldOptions(OptionsResolver $resolver, $object)
    {
        $resolver->setRequired(['integration_fields', 'mautic_fields', 'integration', 'integration_object', 'page']);
        $resolver->setDefined([('lead' === $object) ? 'update_mautic' : 'update_mautic_company']);
        $resolver->setDefaults(
            [
                'special_instructions' => function (Options $options) use ($object) {
                    list($specialInstructions, $alertType) = $options['integration_object']->getFormNotes('leadfield_match');

                    return $specialInstructions;
                },
                'alert_type' => function (Options $options) use ($object) {
                    list($specialInstructions, $alertType) = $options['integration_object']->getFormNotes('leadfield_match');

                    return $alertType;
                },
                'allow_extra_fields'   => true,
                'enable_data_priority' => false,
                'totalFields'          => function (Options $options) {
                    return count($options['integration_fields']);
                },
                'fixedPageNum' => function (Options $options) {
                    return ceil($options['totalFields'] / $options['limit']);
                },
                'limit' => 10,
                'start' => function (Options $options) {
                    return (1 === (int) $options['page']) ? 0 : ((int) $options['page'] - 1) * (int) $options['limit'];
                },
            ]
        );
    }

    /**
     * @param FormView $view
     * @param array    $options
     */
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
