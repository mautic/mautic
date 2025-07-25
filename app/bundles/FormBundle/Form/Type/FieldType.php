<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Collector\ObjectCollectorInterface;
use Mautic\FormBundle\Exception\FieldNotFoundException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class FieldType extends AbstractType
{
    use FormFieldTrait;

    public function __construct(
        private TranslatorInterface $translator,
        private ObjectCollectorInterface $objectCollector,
        private FieldCollectorInterface $fieldCollector,
        private AlreadyMappedFieldCollectorInterface $mappedFieldCollector,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Populate settings
        $cleanMasks = [
            'labelAttributes'     => 'string',
            'inputAttributes'     => 'string',
            'containerAttributes' => 'string',
            'label'               => 'strict_html',
            'helpMessage'         => 'strict_html',
        ];

        $addHelpMessage         =
        $addShowLabel           =
        $allowCustomAlias       =
        $addDefaultValue        =
        $addLabelAttributes     =
        $addInputAttributes     =
        $addContainerAttributes =
        $addMappedFieldList     =
        $addSaveResult          =
        $addBehaviorFields      =
        $addIsRequired          = true;

        if (!empty($options['customParameters'])) {
            $type = 'custom';

            $customParams    = $options['customParameters'];
            $formTypeOptions = [
                'required' => false,
                'label'    => false,
            ];
            if (!empty($customParams['formTypeOptions'])) {
                $formTypeOptions = array_merge($formTypeOptions, $customParams['formTypeOptions']);
            }

            $addFields = [
                'labelText',
                'addHelpMessage',
                'addShowLabel',
                'labelText',
                'addDefaultValue',
                'addLabelAttributes',
                'labelAttributesText',
                'addInputAttributes',
                'inputAttributesText',
                'addContainerAttributes',
                'containerAttributesText',
                'addMappedFieldList',
                'addSaveResult',
                'addBehaviorFields',
                'addIsRequired',
                'addHtml',
            ];

            foreach ($addFields as $f) {
                if (isset($customParams['builderOptions'][$f])) {
                    ${$f} = (bool) $customParams['builderOptions'][$f];
                }
            }
        } else {
            $type = $options['data']['type'];
            switch ($type) {
                case 'freetext':
                    $addHelpMessage      = $addDefaultValue      = $addIsRequired      = $addMappedFieldList      = $addSaveResult      = $addBehaviorFields      = false;
                    $labelText           = 'mautic.form.field.form.header';
                    $showLabelText       = 'mautic.form.field.form.showheader';
                    $inputAttributesText = 'mautic.form.field.form.freetext_attributes';
                    $labelAttributesText = 'mautic.form.field.form.header_attributes';

                    // Allow html
                    $cleanMasks['properties'] = 'html';
                    break;
                case 'freehtml':
                    $addHelpMessage      = $addDefaultValue      = $addIsRequired      = $addMappedFieldList      = $addSaveResult      = $addBehaviorFields      = false;
                    $labelText           = 'mautic.form.field.form.header';
                    $showLabelText       = 'mautic.form.field.form.showheader';
                    $inputAttributesText = 'mautic.form.field.form.freehtml_attributes';
                    $labelAttributesText = 'mautic.form.field.form.header_attributes';
                    // Allow html
                    $cleanMasks['properties'] = 'html';
                    break;
                case 'button':
                    $addHelpMessage = $addShowLabel = $addDefaultValue = $addLabelAttributes = $addIsRequired = $addMappedFieldList = $addSaveResult = $addBehaviorFields = false;
                    break;
                case 'hidden':
                    $addHelpMessage = $addShowLabel = $addLabelAttributes = $addIsRequired = false;
                    break;
                case 'captcha':
                    $addShowLabel = $addIsRequired = $addDefaultValue = $addMappedFieldList = $addSaveResult = $addBehaviorFields = false;
                    break;
                case 'pagebreak':
                    $addShowLabel = $allowCustomAlias = $addHelpMessage = $addIsRequired = $addDefaultValue = $addMappedFieldList = $addSaveResult = $addBehaviorFields = false;
                    break;
                case 'select':
                    $cleanMasks['properties']['list']['list']['label'] = 'strict_html';
                    break;
                case 'checkboxgrp':
                case 'radiogrp':
                    $cleanMasks['properties']['optionlist']['list']['label'] = 'strict_html';
                    break;
                case 'file':
                    $addShowLabel = $addDefaultValue = $addBehaviorFields = false;
                    break;
            }
        }

        // disable progressing profiling  for conditional fields
        if (!empty($options['data']['parent'])) {
            $addBehaviorFields = false;
            $builder->add(
                'conditions',
                FormFieldConditionType::class,
                [
                    'label'      => false,
                    'data'       => $options['data']['conditions'] ?? [],
                    'formId'     => $options['data']['formId'],
                    'parent'     => $options['data']['parent'] ?? null,
                ]
            );
        }

        $builder->add(
            'parent',
            HiddenType::class,
            [
                'label'=> false,
            ]
        );

        // Build form fields
        $builder->add(
            'label',
            TextType::class,
            [
                'label'       => !empty($labelText) ? $labelText : 'mautic.form.field.form.label',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(
                        ['message' => 'mautic.form.field.label.notblank']
                    ),
                ],
            ]
        );

        if ($allowCustomAlias) {
            $builder->add(
                'alias',
                TextType::class,
                [
                    'label'      => 'mautic.form.field.form.alias',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.form.alias.tooltip',
                    ],
                    'disabled' => (!empty($options['data']['id']) && !str_contains($options['data']['id'], 'new')) ? true : false,
                    'required' => false,
                ]
            );
        }

        if ($addShowLabel) {
            $default = (!isset($options['data']['showLabel'])) ? true : (bool) $options['data']['showLabel'];
            $builder->add(
                'showLabel',
                YesNoButtonGroupType::class,
                [
                    'label' => (!empty($showLabelText)) ? $showLabelText : 'mautic.form.field.form.showlabel',
                    'data'  => $default,
                ]
            );
        }

        if ($addDefaultValue) {
            $builder->add(
                'defaultValue',
                ('textarea' == $type) ? TextareaType::class : TextType::class,
                [
                    'label'      => 'mautic.core.defaultvalue',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        }

        if ($addHelpMessage) {
            $builder->add(
                'helpMessage',
                TextType::class,
                [
                    'label'      => 'mautic.form.field.form.helpmessage',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.helpmessage',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addIsRequired) {
            $default = (!isset($options['data']['isRequired'])) ? false : (bool) $options['data']['isRequired'];
            $builder->add(
                'isRequired',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.core.required',
                    'data'  => $default,
                ]
            );

            $builder->add(
                'validationMessage',
                TextType::class,
                [
                    'label'      => 'mautic.form.field.form.validationmsg',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'tooltip'      => $this->translator->trans('mautic.core.form.default').': '.$this->translator->trans('mautic.form.field.generic.required', [], 'validators'),
                        'data-show-on' => '{"formfield_isRequired_1": "checked"}',
                    ],
                    'required'   => false,
                ]
            );
        }

        if ($addLabelAttributes) {
            $builder->add(
                'labelAttributes',
                TextType::class,
                [
                    'label'      => (!empty($labelAttributesText)) ? $labelAttributesText : 'mautic.form.field.form.labelattr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.attr',
                        'maxlength' => '191',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addInputAttributes) {
            $builder->add(
                'inputAttributes',
                TextType::class,
                [
                    'label'      => (!empty($inputAttributesText)) ? $inputAttributesText : 'mautic.form.field.form.inputattr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.attr',
                        'maxlength' => '191',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addContainerAttributes) {
            $builder->add(
                'containerAttributes',
                TextType::class,
                [
                    'label'      => 'mautic.form.field.form.container_attr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.container_attr',
                        'maxlength' => '191',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addSaveResult) {
            $default = (!isset($options['data']['saveResult']) || null === $options['data']['saveResult']) ? true
                : (bool) $options['data']['saveResult'];
            $builder->add(
                'saveResult',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.saveresult',
                    'data'  => $default,
                    'attr'  => [
                        'tooltip' => 'mautic.form.field.help.saveresult',
                    ],
                ]
            );
        }

        if ($addBehaviorFields) {
            $alwaysDisplay = $options['data']['alwaysDisplay'] ?? false;
            $builder->add(
                'alwaysDisplay',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.always_display',
                    'attr'  => [
                        'tooltip' => 'mautic.form.field.form.always_display.tooltip',
                    ],
                    'data'  => $alwaysDisplay,
                ]
            );

            $default = (!isset($options['data']['showWhenValueExists']) || null === $options['data']['showWhenValueExists']) ? true
                : (bool) $options['data']['showWhenValueExists'];
            $builder->add(
                'showWhenValueExists',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.show.when.value.exists',
                    'data'  => $default,
                    'attr'  => [
                        'tooltip'      => 'mautic.form.field.help.show.when.value.exists',
                        'data-show-on' => '{"formfield_alwaysDisplay_0": "checked"}',
                    ],
                ]
            );

            $builder->add(
                'showAfterXSubmissions',
                TextType::class,
                [
                    'label'      => 'mautic.form.field.form.show.after.x.submissions',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.form.field.help.show.after.x.submissions',
                        'data-show-on' => '{"formfield_alwaysDisplay_0": "checked"}',
                    ],
                    'required' => false,
                ]
            );

            $isAutoFillValue = (!isset($options['data']['isAutoFill'])) ? false : (bool) $options['data']['isAutoFill'];
            $builder->add(
                'isAutoFill',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.auto_fill',
                    'data'  => $isAutoFillValue,
                    'attr'  => [
                        'class'   => 'auto-fill-data',
                        'tooltip' => 'mautic.form.field.help.auto_fill',
                    ],
                ]
            );

            $isReadOnlyValue = (bool) ($options['data']['isReadOnly'] ?? false);
            $builder->add(
                'isReadOnly',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.read_only',
                    'data'  => $isReadOnlyValue,
                    'attr'  => [
                        'class'           => 'read-only-data',
                        'tooltip'         => 'mautic.form.field.help.auto_fill',
                        'data-disable-on' => '{"formfield_isAutoFill_0": "checked"}',
                        'data-enable-on'  => '{"formfield_isAutoFill_1": "checked"}',
                    ],
                ]
            );
        }

        $func = function (FormEvent $event) use ($addMappedFieldList, $type) {
            $fieldData = $event->getData();
            $form      = $event->getForm();

            if ($addMappedFieldList) {
                $mappedObject = $fieldData['mappedObject'] ?? 'contact';
                $mappedField  = $fieldData['mappedField'] ?? null;
                $form->add(
                    'mappedObject',
                    ChoiceType::class,
                    [
                        'choices'    => $this->objectCollector->getObjects()->toChoices(),
                        'label'      => 'mautic.form.field.form.mapped.object',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'    => 'form-control',
                            'tooltip'  => 'mautic.form.field.help.mapped.object',
                            'onchange' => 'Mautic.fetchFieldsOnObjectChange();',
                        ],
                        'required' => false,
                        'data'     => $mappedObject,
                    ]
                );

                $fields       = $this->fieldCollector->getFields($mappedObject);
                $mappedFields = [];
                if (in_array('formId', $fieldData)) {
                    $mappedFields = $this->mappedFieldCollector->getFields((string) $fieldData['formId'], $mappedObject);
                }
                $fields = $fields->removeFieldsWithKeys($mappedFields, (string) $mappedField);

                $form->add(
                    'mappedField',
                    ChoiceType::class,
                    [
                        'choices'     => $fields->toChoices(),
                        'choice_attr' => function ($val) use ($fields): array {
                            try {
                                $field = $fields->getFieldByKey($val);
                                if ($field->isListType()) {
                                    return ['data-list-type' => 1];
                                }
                            } catch (FieldNotFoundException) {
                            }

                            return [];
                        },
                        'label'      => 'mautic.form.field.form.mapped.field',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.form.field.help.mapped.field',
                        ],
                        'required' => false,
                        'data'     => $mappedField ?? (empty($fieldData['id']) ? $this->getDefaultMappedField((string) $type) : ''),
                    ]
                );

                $form->add(
                    'originalMappedField',
                    HiddenType::class,
                    [
                        'label'    => false,
                        'required' => false,
                        'data'     => $mappedField,
                    ]
                );
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);

        $builder->add('type', HiddenType::class);

        $update = (!empty($options['data']['id'])) ? true : false;
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'ri-edit-line';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'ri-add-line';
        }

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'save_text'       => $btnValue,
                'save_icon'       => $btnIcon,
                'apply_text'      => false,
                'container_class' => 'bottom-form-buttons',
            ]
        );

        $builder->add(
            'formId',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );

        // Put properties last so that the other values are available to form events
        $propertiesData = $options['data']['properties'] ?? [];
        if (!empty($options['customParameters'])) {
            $formTypeOptions = array_merge($formTypeOptions, ['data' => $propertiesData]);
            $builder->add('properties', $customParams['formType'], $formTypeOptions);
        } else {
            switch ($type) {
                case 'select':
                case 'country':
                    $builder->add(
                        'properties',
                        FormFieldSelectType::class,
                        [
                            'field_type' => $type,
                            'label'      => false,
                            'parentData' => $options['data'],
                            'data'       => $propertiesData,
                        ]
                    );
                    break;
                case 'checkboxgrp':
                case 'radiogrp':
                    $builder->add(
                        'properties',
                        FormFieldGroupType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'freetext':
                    $builder->add(
                        'properties',
                        FormFieldTextType::class,
                        [
                            'required' => false,
                            'label'    => false,
                            'editor'   => true,
                            'data'     => $propertiesData,
                        ]
                    );
                    break;
                case 'freehtml':
                    $builder->add(
                        'properties',
                        FormFieldHTMLType::class,
                        [
                            'required' => false,
                            'label'    => false,
                            'editor'   => true,
                            'data'     => $propertiesData,
                        ]
                    );
                    break;
                case 'number':
                    $builder->add(
                        'properties',
                        FormFieldNumberType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'date':
                case 'email':
                case 'text':
                case 'url':
                case 'tel':
                    $builder->add(
                        'properties',
                        FormFieldPlaceholderType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'captcha':
                    $builder->add(
                        'properties',
                        FormFieldCaptchaType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'pagebreak':
                    $builder->add(
                        'properties',
                        FormFieldPageBreakType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'file':
                    if (!isset($propertiesData['public'])) {
                        $propertiesData['public'] = false;
                    }
                    $builder->add(
                        'properties',
                        FormFieldFileType::class,
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
            }
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($cleanMasks));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'customParameters' => false,
            ]
        );

        $resolver->setDefined(['customParameters']);
    }

    public function getBlockPrefix(): string
    {
        return 'formfield';
    }

    private function getDefaultMappedField(string $type): string
    {
        return match ($type) {
            'email'         => 'email',
            'country'       => 'country',
            'tel'           => 'phone',
            'companyLookup' => 'company',
            default         => '',
        };
    }
}
