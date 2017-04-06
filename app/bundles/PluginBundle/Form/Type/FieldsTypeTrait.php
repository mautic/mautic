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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;

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
        $start
    ) {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $integrationFields, $mauticFields, $fieldObject, $limit, $start) {
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

                    if (is_array($details) && !empty($details['required'])) {
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

                $paginatedFields = array_slice($fields, $start, $limit);
                foreach ($paginatedFields as $field => $details) {
                    $matched = isset($fieldData[$field]);
                    $required = (int) !empty($integrationFields[$field]['required']);

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
                                'data'        => isset($options[$updateName][$field]) ? (int) $options[$updateName][$field] : 1,
                                'empty_value' => false,
                                'attr'        => [
                                    'data-toggle' => 'tooltip',
                                    'title'       => 'mautic.plugin.direction.data.update',
                                ],
                            ]
                        );
                    }

                    $constraints = ($required) ? [
                        new NotBlank(
                            [
                                'message' => 'mautic.core.value.required',
                            ]
                        ),
                        new NotEqualTo(
                            [
                                'message' => 'mautic.core.value.required',
                                'value'   => '-1',
                            ]
                        ),
                    ] : [];

                    $form->add(
                        'm_'.$index,
                        'choice',
                        [
                            'choices'    => $mauticFields,
                            'label'      => false,
                            'data'       => $matched ? $fieldData[$field] : '',
                            'label_attr' => ['class' => 'control-label'],
                            'attr'       => [
                                'class'            => 'field-selector',
                                'data-placeholder' => ' ',
                                'data-required'    => $required,
                                'data-value'       => $matched ? $fieldData[$field] : '',
                                'data-choices'     => $mauticFields,
                            ],
                            'constraints' => $constraints,
                        ]
                    );
                    $form->add(
                        'i_'.$index,
                        HiddenType::class,
                        [
                            'data' => $field,
                            'attr' => [
                                'data-required' => $required,
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
                            ],
                        ]
                    );
                }
            }
        );
    }
}
