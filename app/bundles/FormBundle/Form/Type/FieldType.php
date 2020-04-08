<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class FieldType.
 */
class FieldType extends AbstractType
{
    use FormFieldTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $factory;

    /**
     * FieldType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Populate settings
        $cleanMasks = [
            'labelAttributes'     => 'string',
            'inputAttributes'     => 'string',
            'containerAttributes' => 'string',
            'label'               => 'strict_html',
        ];

        $addHelpMessage         =
        $addShowLabel           =
        $allowCustomAlias       =
        $addDefaultValue        =
        $addLabelAttributes     =
        $addInputAttributes     =
        $addContainerAttributes =
        $addLeadFieldList       =
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
                'addLeadFieldList',
                'addSaveResult',
                'addBehaviorFields',
                'addIsRequired',
                'addHtml',
            ];
            foreach ($addFields as $f) {
                if (isset($customParams['builderOptions'][$f])) {
                    $$f = (bool) $customParams['builderOptions'][$f];
                }
            }
        } else {
            $type = $options['data']['type'];
            switch ($type) {
                case 'freetext':
                    $addHelpMessage      = $addDefaultValue      = $addIsRequired      = $addLeadFieldList      = $addSaveResult      = $addBehaviorFields      = false;
                    $labelText           = 'mautic.form.field.form.header';
                    $showLabelText       = 'mautic.form.field.form.showheader';
                    $inputAttributesText = 'mautic.form.field.form.freetext_attributes';
                    $labelAttributesText = 'mautic.form.field.form.header_attributes';

                    // Allow html
                    $cleanMasks['properties'] = 'html';
                    break;
                case 'freehtml':
                    $addHelpMessage      = $addDefaultValue      = $addIsRequired      = $addLeadFieldList      = $addSaveResult      = $addBehaviorFields      = false;
                    $labelText           = 'mautic.form.field.form.header';
                    $showLabelText       = 'mautic.form.field.form.showheader';
                    $inputAttributesText = 'mautic.form.field.form.freehtml_attributes';
                    $labelAttributesText = 'mautic.form.field.form.header_attributes';
                    // Allow html
                    $cleanMasks['properties'] = 'html';
                    break;
                case 'button':
                    $addHelpMessage = $addShowLabel = $addDefaultValue = $addLabelAttributes = $addIsRequired = $addLeadFieldList = $addSaveResult = $addBehaviorFields = false;
                    break;
                case 'hidden':
                    $addHelpMessage = $addShowLabel = $addLabelAttributes = $addIsRequired = false;
                    break;
                case 'captcha':
                    $addShowLabel = $addIsRequired = $addDefaultValue = $addLeadFieldList = $addSaveResult = $addBehaviorFields = false;
                    break;
                case 'pagebreak':
                    $addShowLabel = $allowCustomAlias = $addHelpMessage = $addIsRequired = $addDefaultValue = $addLeadFieldList = $addSaveResult = $addBehaviorFields = false;
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

        // Build form fields
        $builder->add(
            'label',
            'text',
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
                'text',
                [
                    'label'      => 'mautic.form.field.form.alias',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.form.alias.tooltip',
                    ],
                    'disabled' => (!empty($options['data']['id']) && strpos($options['data']['id'], 'new') === false) ? true : false,
                    'required' => false,
                ]
            );
        }

        if ($addShowLabel) {
            $default = (!isset($options['data']['showLabel'])) ? true : (bool) $options['data']['showLabel'];
            $builder->add(
                'showLabel',
                'yesno_button_group',
                [
                    'label' => (!empty($showLabelText)) ? $showLabelText : 'mautic.form.field.form.showlabel',
                    'data'  => $default,
                ]
            );
        }

        if ($addDefaultValue) {
            $builder->add(
                'defaultValue',
                ($type == 'textarea') ? 'textarea' : 'text',
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
                'text',
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
                'yesno_button_group',
                [
                    'label' => 'mautic.core.required',
                    'data'  => $default,
                ]
            );

            $builder->add(
                'validationMessage',
                'text',
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
                'text',
                [
                    'label'      => (!empty($labelAttributesText)) ? $labelAttributesText : 'mautic.form.field.form.labelattr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.attr',
                        'maxlength' => '255',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addInputAttributes) {
            $builder->add(
                'inputAttributes',
                'text',
                [
                    'label'      => (!empty($inputAttributesText)) ? $inputAttributesText : 'mautic.form.field.form.inputattr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.attr',
                        'maxlength' => '255',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addContainerAttributes) {
            $builder->add(
                'containerAttributes',
                'text',
                [
                    'label'      => (!empty($containerAttributesText)) ? $containerAttributesText : 'mautic.form.field.form.container_attr',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'     => 'form-control',
                        'tooltip'   => 'mautic.form.field.help.container_attr',
                        'maxlength' => '255',
                    ],
                    'required' => false,
                ]
            );
        }

        if ($addSaveResult) {
            $default = (!isset($options['data']['saveResult']) || $options['data']['saveResult'] === null) ? true
                : (bool) $options['data']['saveResult'];
            $builder->add(
                'saveResult',
                'yesno_button_group',
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
            $default = (!isset($options['data']['showWhenValueExists']) || $options['data']['showWhenValueExists'] === null) ? true
                : (bool) $options['data']['showWhenValueExists'];
            $builder->add(
                'showWhenValueExists',
                'yesno_button_group',
                [
                    'label' => 'mautic.form.field.form.show.when.value.exists',
                    'data'  => $default,
                    'attr'  => [
                        'tooltip' => 'mautic.form.field.help.show.when.value.exists',
                    ],
                ]
            );

            $builder->add(
                'showAfterXSubmissions',
                'text',
                [
                    'label'      => 'mautic.form.field.form.show.after.x.submissions',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.show.after.x.submissions',
                    ],
                    'required' => false,
                ]
            );

            $isAutoFillValue = (!isset($options['data']['isAutoFill'])) ? false : (bool) $options['data']['isAutoFill'];
            $builder->add(
                'isAutoFill',
                'yesno_button_group',
                [
                    'label' => 'mautic.form.field.form.auto_fill',
                    'data'  => $isAutoFillValue,
                    'attr'  => [
                        'class'   => 'auto-fill-data',
                        'tooltip' => 'mautic.form.field.help.auto_fill',
                    ],
                ]
            );
        }

        if ($addLeadFieldList) {
            if (!isset($options['data']['leadField'])) {
                switch ($type) {
                    case 'email':
                        $data = 'email';
                        break;
                    case 'country':
                        $data = 'country';
                        break;
                    case 'tel':
                        $data = 'phone';
                        break;
                    default:
                        $data = '';
                        break;
                }
            } elseif (isset($options['data']['leadField'])) {
                $data = $options['data']['leadField'];
            } else {
                $data = '';
            }

            $builder->add(
                'leadField',
                'choice',
                [
                    'choices'     => $options['leadFields'],
                    'choice_attr' => function ($val, $key, $index) use ($options) {
                        $objects = ['lead', 'company'];
                        foreach ($objects as $object) {
                            if (!empty($options['leadFieldProperties'][$object][$val]) && (in_array($options['leadFieldProperties'][$object][$val]['type'], FormFieldHelper::getListTypes()) || !empty($options['leadFieldProperties'][$object][$val]['properties']['list']) || !empty($options['leadFieldProperties'][$object][$val]['properties']['optionlist']))) {
                                return ['data-list-type' => 1];
                            }
                        }

                        return [];
                    },
                    'label'      => 'mautic.form.field.form.lead_field',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.form.field.help.lead_field',
                    ],
                    'required' => false,
                    'data'     => $data,
                ]
            );
        }

        $builder->add('type', 'hidden');

        $update = (!empty($options['data']['id'])) ? true : false;
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'save_text'       => $btnValue,
                'save_icon'       => $btnIcon,
                'apply_text'      => false,
                'container_class' => 'bottom-form-buttons',
            ]
        );

        $builder->add(
            'formId',
            'hidden',
            [
                'mapped' => false,
            ]
        );

        // Put properties last so that the other values are available to form events

        $propertiesData = (isset($options['data']['properties'])) ? $options['data']['properties'] : [];
        // Dependent Fields Configuration Start - 03-31-2020
        $fields      = $this->fieldModel->getSessionFields($options['data']['formId']);
        $objectForm  = 'Lead';
        $Leadfields  = $this->fieldModel->getObjectFields($objectForm);

        $CountyChoices = [
            'country'  => FormFieldHelper::getCountryChoices(),
        ];
        $StateChoices = [
            'state'   => FormFieldHelper::getRegionChoices(),
        ];
        $viewOnly = $this->formModel->getCustomComponents()['viewOnlyFields'];

        $choices               = ['' => ''];
        $dependentlist         = [];
        $dependentoptions      = [];
        $getDependentvalue     = [];
        $setDependentvalue     = ['' => ''];
        $setValue              = [];

        if (!empty($options['data']['dependent'])) {
            $dependentsData = (isset($options['data']['dependent'])) ? $options['data']['dependent'] : '';
        } else {
            $dependentsData = (isset($options['data']['properties']['dependent'])) ? $options['data']['properties']['dependent'] : [];
        }

        if (!empty($options['data']['properties']['dependentValue'])) {
            $getValue = (isset($options['data']['properties']['dependentValue'])) ? $options['data']['properties']['dependentValue'] : '';
            if (is_array($getValue)) {
                if (!empty($getValue)) {
                    foreach ($getValue as $setValues) {
                        $setValue[$setValues] = $setValues;
                    }
                    $dependentsValue = $setValue;
                }
            } else {
                $setValue[$getValue] = $getValue;
                $dependentsValue     = $setValue;
            }
        } else {
            $getValue = (isset($options['data']['dependentValue'])) ? $options['data']['dependentValue'] : '';
            if (is_array($getValue)) {
                if (!empty($getValue)) {
                    foreach ($getValue as $setValues) {
                        $setValue[$setValues] = $setValues;
                    }
                    $dependentsValue = $setValue;
                }
            } else {
                $setValue[$getValue] = $getValue;
                $dependentsValue     = $setValue;
            }
            if (!empty($options['data']['dependentLabel'])) {
                $setDependentvalue[$options['data']['dependentLabel']] = $dependentsValue;
            }
        }
        if (!empty($options['data']['dependentOperator'])) {
            $dependentOperator = (isset($options['data']['dependentOperator'])) ? $options['data']['dependentOperator'] : '';
        } else {
            $dependentOperator = (isset($options['data']['properties']['dependentOperator'])) ? $options['data']['properties']['dependentOperator'] : '';
        }

        if (!empty($options['data']['dependentLabel'])) {
            $dependentsLabel = (isset($options['data']['dependentLabel'])) ? $options['data']['dependentLabel'] : '';
            foreach ($fields as $dL) {
                if ($dL['alias'] == $dependentsLabel) {
                    if (!empty($dL['properties']['list']['list'])) {
                        $getDependentvalue = $dL['properties']['list']['list'];
                    } elseif (!empty($dL['properties']['optionlist']['list'])) {
                        $getDependentvalue =$dL['properties']['optionlist']['list'];
                    }
                    if (!empty($getDependentvalue)) {
                        foreach ($getDependentvalue as $setDependentvalues) {
                            if (isset($setDependentvalues['value']) && isset($setDependentvalues['label'])) {
                                $setDependentvalue[$setDependentvalues['value']] = $setDependentvalues['label'];
                            } elseif (is_array($setDependentvalues)) {
                                foreach ($setDependentvalues as $optgroup => $setDependentvalues) {
                                    $setDependentvalue[$setDependentvalues] = $setDependentvalues;
                                }
                            } elseif (!is_array($setDependentvalues)) {
                                $setDependentvalue[$setDependentvalues] = $setDependentvalues;
                            }
                        }
                    }
                }
            }
        } else {
            $dependentsLabel = (isset($options['data']['properties']['dependentLabel'])) ? $options['data']['properties']['dependentLabel'] : '';
            foreach ($fields as $dL) {
                if ($dL['alias'] == $dependentsLabel) {
                    if (!empty($dL['properties']['list']['list'])) {
                        $getDependentvalue = $dL['properties']['list']['list'];
                    } elseif (!empty($dL['properties']['optionlist']['list'])) {
                        $getDependentvalue =$dL['properties']['optionlist']['list'];
                    }
                    if (!empty($getDependentvalue)) {
                        foreach ($getDependentvalue as $setDependentvalues) {
                            if (isset($setDependentvalues['value']) && isset($setDependentvalues['label'])) {
                                $setDependentvalue[$setDependentvalues['value']] = $setDependentvalues['label'];
                            } elseif (is_array($setDependentvalues)) {
                                foreach ($setDependentvalues as $optgroup => $setDependentvalues) {
                                    $setDependentvalue[$setDependentvalues] = $setDependentvalues;
                                }
                            } elseif (!is_array($setDependentvalues)) {
                                $setDependentvalue[$setDependentvalues] = $setDependentvalues;
                            }
                        }
                    }
                }
            }
        }

        foreach ($fields as $f) {
            if (in_array(
                $f['type'],
                    array_merge(
                        ['button', 'captcha', 'date', 'email', 'number', 'text', 'url', 'tel']
                    ),
                    true
                )) {
                continue;
            }
            // Dependent Option Convert to Json
            if (!empty($f['leadField']) && (!empty($f['properties']['syncList']) && $f['properties']['syncList'] == '1')) {
                if ($f['leadField'] == 'state') {
                    $dependentoptions[$f['alias']] = $StateChoices['state'];
                } elseif ($f['leadField'] == 'country') {
                    foreach ($CountyChoices as $ListsType => $ListsChoices) {
                        $dependentoptions[$f['alias']] = $ListsChoices;
                    }
                } else {
                    foreach ($Leadfields[0] as $EditCorefield) {
                        if ($EditCorefield['alias'] == $f['leadField']) {
                            if (!empty($EditCorefield['properties']['list'])) {
                                $EditCoredependentlist = $EditCorefield['properties']['list'];
                            } elseif (!empty($EditCorefield['properties']['optionlist'])) {
                                $EditCoredependentlist = $EditCorefield['properties']['optionlist'];
                            }
                            if (!empty($EditCoredependentlist)) {
                                $dependentoptions[$f['alias']] = [];
                                foreach ($EditCoredependentlist as $EditCoredependentlists) {
                                    if (is_array($EditCoredependentlists) && isset($EditCoredependentlists['value']) && isset($EditCoredependentlists['label'])) {
                                        $dependentoptions[$f['alias']][$EditCoredependentlists['value']] = $EditCoredependentlists['label'];
                                    //$setDependentvalue[$EditCoredependentlists['value']] = $EditCoredependentlists['label'];
                                    } elseif (is_array($EditCoredependentlists)) {
                                        foreach ($EditCoredependentlists as $optgroup => $EditCoredependentlists) {
                                            $dependentoptions[$f['alias']][$EditCoredependentlists] = $EditCoredependentlists;
                                        }
                                    } elseif (!is_array($EditCoredependentlists)) {
                                        $dependentoptions[$f['alias']][$EditCoredependentlists] = $EditCoredependentlists;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if (empty($f['leadField']) && empty($f['properties']['syncList'])) {
                    if (!empty($f['properties']['list']['list'])) {
                        $dependentlist        = $f['properties']['list']['list'];
                        $choices[$f['alias']] = $f['label'];
                    } elseif (!empty($f['properties']['optionlist']['list'])) {
                        $dependentlist =$f['properties']['optionlist']['list'];
                    }
                    if (!empty($dependentlist)) {
                        $dependentoptions[$f['alias']] = [];
                        foreach ($dependentlist as $dependentoption) {
                            if (is_array($dependentoption) && isset($dependentoption['value']) && isset($dependentoption['label'])) {
                                $dependentoptions[$f['alias']][$dependentoption['value']] = $dependentoption['label'];
                            //$setDependentvalue[$dependentoption['value']] = $dependentoption['label'];
                            } elseif (is_array($dependentoption)) {
                                foreach ($dependentoption as $optgroup => $dependentoption) {
                                    $dependentoptions[$f['alias']][$dependentoption] = $dependentoption;
                                }
                            } elseif (!is_array($dependentoption)) {
                                $dependentoptions[$f['alias']][$dependentoption] = $dependentoption;
                                //$setDependentvalue[$dependentoption['value']] = $dependentoption['label'];
                            }
                        }
                    }
                }
            }
            $choices[$f['alias']] = $f['label'];
        }
        // Dependent Fields Configuration End - 03-31-2020

        if (!empty($options['customParameters'])) {
            $formTypeOptions = array_merge($formTypeOptions, ['data' => $propertiesData]);
            $builder->add('properties', $customParams['formType'], $formTypeOptions);
        } else {
            switch ($type) {
                case 'select':
                case 'country':
                    $builder->add(
                        'properties',
                        'formfield_select',
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
                        'formfield_group',
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'freetext':
                    $builder->add(
                        'properties',
                        'formfield_text',
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
                        'formfield_html',
                        [
                            'required' => false,
                            'label'    => false,
                            'editor'   => true,
                            'data'     => $propertiesData,
                        ]
                    );
                    break;
                case 'date':
                case 'email':
                case 'number':
                case 'text':
                case 'url':
                case 'tel':
                    $builder->add(
                        'properties',
                        'formfield_placeholder',
                        [
                            'label' => false,
                            'data'  => $propertiesData,
                        ]
                    );
                    break;
                case 'captcha':
                    $builder->add(
                        'properties',
                        'formfield_captcha',
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

            switch ($type) {
                case 'freetext':
                case 'checkboxgrp':
                case 'radiogrp':
                case 'select':
                case 'country':
                case 'date':
                case 'email':
                case 'number':
                case 'text':
                case 'url':
                case 'tel':
                case 'file':
                    $builder->add(
                        'dependent',
                        'yesno_button_group',
                        [
                            'attr'  => [
                                'class' => 'form-control',
                            ],
                            'label' => 'mautic.form.field.form.dependency.label',
                            'data'  => $dependentsData,
                        ]
                    );
                    $builder->add(
                        'dependentLabel',
                        'choice',
                        [
                            'choices'     => $choices,
                            'multiple'    => false,
                            'label'       => 'mautic.form.field.form.dependent.field.mapping',
                            'label_attr'  => ['class' => 'control-label'],
                            'empty_value' => 'mautic.core.select',
                            'attr'        => [
                                'class'              => 'form-control',
                                'data-show-on'       => '{"formfield_dependent_0": ""}',
                                'onchange'           => 'Mautic.dependentupdateFormFieldValues(this);',
                                'data-field-options' => json_encode($dependentoptions),
                            ],
                            'required'    => false,
                            'data'        => $dependentsLabel,
                        ]
                    );
                    $builder->add(
                        'dependentOperator',
                        'choice',
                        [
                            'choices'  => ['equals' => 'equals', 'in' => 'including'],
                            'multiple' => false,
                            'label'    => 'mautic.lead.lead.submitaction.operator',
                            'attr'     => [
                                'class'        => 'form-control',
                                'data-show-on' => '{"formfield_dependent_0": ""}',
                                'onchange'     => 'Mautic.getDependentOperator(this);',
                            ],
                            'required' => false,
                            'data'     => $dependentOperator,
                        ]
                    );
                    $builder->add(
                        'dependentValue',
                        'choice',
                        [
                            'choices'  => $setDependentvalue,
                            'multiple' => true,
                            'label'    => 'mautic.core.value',
                            'attr'     => [
                                'class'        => 'form-control',
                                'data-show-on' => '{"formfield_dependent_0": ""}',
                            ],
                            'required' => false,
                            'data'     => $dependentsValue,
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

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'customParameters' => false,
            ]
        );

        $resolver->setOptional(['customParameters', 'leadFieldProperties']);

        $resolver->setRequired(['leadFields']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'formfield';
    }
}
