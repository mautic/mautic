<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\TelType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

trait EntityFieldsBuildFormTrait
{
    private function getFormFields(FormBuilderInterface $builder, array $options, $object = 'lead')
    {
        $cleaningRules = [];
        $fieldValues   = [];
        $isObject      = false;
        if (!empty($options['data'])) {
            $isObject    = is_object($options['data']);
            $fieldValues = ($isObject) ? $options['data']->getFields() : $options['data'];
        }
        $mapped = !$isObject;

        foreach ($options['fields'] as $field) {
            if (false === $field['isPublished'] || $field['object'] !== $object) {
                continue;
            }
            $attr       = ['class' => 'form-control'];
            $properties = $field['properties'];
            $type       = $field['type'];
            $required   = ($isObject) ? $field['isRequired'] : false;
            $alias      = $field['alias'];
            $group      = $field['group'];

            if ($field['isUniqueIdentifer']) {
                $attr['data-unique-identifier'] = $field['alias'];
            }

            if ($isObject) {
                $value = (isset($fieldValues[$group][$alias]['value'])) ?
                    $fieldValues[$group][$alias]['value'] : $field['defaultValue'];
            } else {
                $value = (isset($fieldValues[$alias])) ? $fieldValues[$alias] : '';
            }

            $constraints = [];
            if ($required && empty($options['ignore_required_constraints'])) {
                $constraints[] = new NotBlank(
                    ['message' => 'mautic.lead.customfield.notblank']
                );
            } elseif (!empty($options['ignore_required_constraints'])) {
                $required            = false;
                $field['isRequired'] = false;
            }

            switch ($type) {
                case 'number':
                case NumberType::class:
                    $formType = NumberType::class;
                    if (empty($properties['scale'])) {
                        $properties['scale'] = null;
                    } //ensure default locale is used
                    else {
                        $properties['scale'] = (int) $properties['scale'];
                    }

                    if ('' === $value) {
                        // Prevent transform errors
                        $value = null;
                    }

                    $builder->add(
                        $alias,
                        $formType,
                        [
                            'required'      => $required,
                            'label'         => $field['label'],
                            'label_attr'    => ['class' => 'control-label'],
                            'attr'          => $attr,
                            'data'          => (null !== $value) ? (float) $value : $value,
                            'mapped'        => $mapped,
                            'constraints'   => $constraints,
                            'scale'         => $properties['scale'],
                            'rounding_mode' => isset($properties['roundmode']) ? (int) $properties['roundmode'] : 0,
                        ]
                    );
                    break;
                case 'date':
                case DateType::class:
                case 'datetime':
                case DateTimeType::class:
                case 'time':
                case TimeType::class:
                    switch ($type) {
                        case 'date':
                        case DateType::class:
                            $formType = DateType::class;
                            break;
                        case 'datetime':
                        case DateTimeType::class:
                            $formType = DateTimeType::class;
                            break;
                        case 'time':
                        case TimeType::class:
                            $formType = TimeType::class;
                            break;
                    }
                    $attr['data-toggle'] = $type;
                    $opts                = [
                        'required'    => $required,
                        'label'       => $field['label'],
                        'label_attr'  => ['class' => 'control-label'],
                        'widget'      => 'single_text',
                        'attr'        => $attr,
                        'mapped'      => $mapped,
                        'input'       => 'string',
                        'html5'       => false,
                        'constraints' => $constraints,
                    ];

                    if ($value) {
                        try {
                            $dtHelper = new DateTimeHelper($value, null, 'local');
                        } catch (\Exception $e) {
                            // Rather return empty value than break the page
                            $value = null;
                        }
                    }

                    if (DateTimeType::class === $formType) {
                        $opts['model_timezone'] = 'UTC';
                        $opts['view_timezone']  = date_default_timezone_get();
                        $opts['format']         = 'yyyy-MM-dd HH:mm:ss';
                        $opts['with_seconds']   = true;

                        $opts['data'] = $value ? $dtHelper->toLocalString('Y-m-d H:i:s') : null;
                    } elseif (DateType::class === $formType) {
                        $opts['data'] = $value ? $dtHelper->toLocalString('Y-m-d') : null;
                    } else {
                        $opts['model_timezone'] = 'UTC';
                        $opts['with_seconds']   = true;
                        $opts['view_timezone']  = date_default_timezone_get();
                        $opts['data']           = $value ? $dtHelper->toLocalString('H:i:s') : null;
                    }

                    $builder->addEventListener(
                        FormEvents::PRE_SUBMIT,
                        function (FormEvent $event) use ($alias, $formType) {
                            $data = $event->getData();

                            if (!empty($data[$alias])) {
                                if (false === ($timestamp = strtotime($data[$alias]))) {
                                    $timestamp = null;
                                }
                                if ($timestamp) {
                                    $dtHelper = new DateTimeHelper(date('Y-m-d H:i:s', $timestamp), null, 'local');
                                    switch ($formType) {
                                        case DateTimeType::class:
                                            $data[$alias] = $dtHelper->toLocalString('Y-m-d H:i:s');
                                            break;
                                        case DateType::class:
                                            $data[$alias] = $dtHelper->toLocalString('Y-m-d');
                                            break;
                                        case TimeType::class:
                                            $data[$alias] = $dtHelper->toLocalString('H:i:s');
                                            break;
                                    }
                                }
                            }
                            $event->setData($data);
                        }
                    );

                    $builder->add($alias, $formType, $opts);
                    break;
                case 'select':
                case 'multiselect':
                case 'boolean':
                    if ('multiselect' === $type) {
                        $constraints[] = new Length(['max' => 255]);
                    }

                    $typeProperties = [
                        'required'          => $required,
                        'label'             => $field['label'],
                        'label_attr'        => ['class' => 'control-label'],
                        'attr'              => $attr,
                        'mapped'            => $mapped,
                        'multiple'          => false,
                        'constraints'       => $constraints,
                        'choices_as_values' => true,
                    ];

                    $formType   = ChoiceType::class;
                    $emptyValue = '';
                    if (in_array($type, ['select', 'multiselect']) && !empty($properties['list'])) {
                        $typeProperties['choices']      = FormFieldHelper::parseList($properties['list'], true, false, true);
                        $typeProperties['expanded']     = false;
                        $typeProperties['multiple']     = ('multiselect' === $type);
                        $cleaningRules[$field['alias']] = 'raw';
                    }
                    if ('boolean' === $type && !empty($properties['yes']) && !empty($properties['no'])) {
                        $formType                    = YesNoButtonGroupType::class;
                        $typeProperties['expanded']  = true;
                        $typeProperties['yes_label'] = $properties['yes'];
                        $typeProperties['no_label']  = $properties['no'];
                        $typeProperties['attr']      = [];
                        $emptyValue                  = ' x ';
                        if ('' !== $value && null !== $value) {
                            $value = (int) $value;
                        }
                    }

                    $typeProperties['data']        = 'multiselect' === $type ? FormFieldHelper::parseList($value) : $value;
                    $typeProperties['placeholder'] = $emptyValue;

                    $builder->add($alias, $formType, $typeProperties);
                    break;
                case 'country':
                case CountryType::class:
                case 'region':
                case 'timezone':
                case TimezoneType::class:
                case 'locale':
                case LocaleType::class:
                    switch ($type) {
                        case 'country':
                        case CountryType::class:
                            $choices = FormFieldHelper::getCountryChoices();
                            break;
                        case 'region':
                            $choices = FormFieldHelper::getRegionChoices();
                            break;
                        case 'timezone':
                            $choices = FormFieldHelper::getTimezonesChoices();
                            break;
                        case 'locale':
                            $choices = FormFieldHelper::getLocaleChoices();
                            break;
                    }

                    $builder->add(
                        $alias,
                        ChoiceType::class,
                        [
                            'choices_as_values' => true,
                            'choices'           => $choices,
                            'required'          => $required,
                            'label'             => $field['label'],
                            'label_attr'        => ['class' => 'control-label'],
                            'data'              => $value,
                            'attr'              => [
                                'class'            => 'form-control',
                                'data-placeholder' => $field['label'],
                            ],
                            'mapped'      => $mapped,
                            'multiple'    => false,
                            'expanded'    => false,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
                default:
                    $attr['data-encoding'] = 'raw';
                    switch ($type) {
                        case 'lookup':
                            $formType            = TextType::class;
                            $attr['data-toggle'] = 'field-lookup';
                            $attr['data-action'] = 'lead:fieldList';
                            $attr['data-target'] = $alias;

                            if (!empty($properties['list'])) {
                                $attr['data-options'] = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, array_keys(FormFieldHelper::parseList($properties['list'])));
                            }
                            break;
                        case 'email':
                        case EmailType::class:
                            // Enforce a valid email
                            $formType              = EmailType::class;
                            $attr['data-encoding'] = 'email';
                            $constraints[]         = new Email([
                                    'message' => 'mautic.core.email.required',
                                ]);
                            break;
                        case 'text':
                        case TextType::class:
                            $formType      = TextType::class;
                            $constraints[] = new Length(['max' => 255]);
                            break;
                        case 'multiselect':
                            $formType      = ChoiceType::class;
                            $constraints[] = new Length(['max' => 255]);
                            break;
                        case 'tel':
                        case TelType::class:
                            $formType = TelType::class;
                            break;
                    }

                    $builder->add(
                        $alias,
                        $formType,
                        [
                            'required'   => $field['isRequired'],
                            'label'      => $field['label'],
                            'label_attr' => ['class' => 'control-label'],

                            'attr'        => $attr,
                            'data'        => $value,
                            'mapped'      => $mapped,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
            }
        }

        return $cleaningRules;
    }
}
