<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\BooleanType;
use Mautic\CoreBundle\Form\Type\CountryType;
use Mautic\CoreBundle\Form\Type\LocaleType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Form\Type\MultiselectType;
use Mautic\CoreBundle\Form\Type\RegionType;
use Mautic\CoreBundle\Form\Type\SelectType;
use Mautic\CoreBundle\Form\Type\TimezoneType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Form\FieldAliasToFqcnMap;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

trait EntityFieldsBuildFormTrait
{
    /**
     * @return array<string, 'html'|'raw'>
     */
    private function getFormFields(FormBuilderInterface $builder, array $options, $object = 'lead'): array
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

            try {
                $type = FieldAliasToFqcnMap::getFqcn($type);
            } catch (FieldNotFoundException) {
            }

            if ($field['isUniqueIdentifer']) {
                $attr['data-unique-identifier'] = $field['alias'];
            }

            if ($isObject) {
                $value = $fieldValues[$group][$alias]['value'] ?? $field['defaultValue'];
            } else {
                $value = $fieldValues[$alias] ?? '';
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
                case NumberType::class:
                    if (empty($properties['scale'])) {
                        $properties['scale'] = null;
                    } // ensure default locale is used
                    else {
                        $properties['scale'] = (int) $properties['scale'];
                    }

                    if ('' === $value) {
                        // Prevent transform errors
                        $value = null;
                    }

                    $builder->add(
                        $alias,
                        $type,
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
                case DateType::class:
                case DateTimeType::class:
                case TimeType::class:
                    $opts                = [
                        'required'    => $required,
                        'label'       => $field['label'],
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => $attr,
                        'mapped'      => $mapped,
                        'constraints' => $constraints,
                    ];

                    if (!empty($options['ignore_date_type'])) {
                        $type = TextType::class;
                    } else {
                        $opts['html5']  = false;
                        $opts['input']  = 'string';
                        $opts['widget'] = 'single_text';
                        $opts['html5']  = false;
                        if ($value) {
                            try {
                                $dtHelper = new DateTimeHelper($value, null, 'local');
                            } catch (\Exception) {
                                // Rather return empty value than break the page
                                $value = null;
                            }
                        }
                        if (DateTimeType::class === $type) {
                            $opts['attr']['data-toggle'] = 'datetime';
                            $opts['model_timezone']      = 'UTC';
                            $opts['view_timezone']       = date_default_timezone_get();
                            $opts['format']              = 'yyyy-MM-dd HH:mm:ss';
                            $opts['with_seconds']        = true;

                            $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d H:i:s') : null;
                        } elseif (DateType::class === $type) {
                            $opts['attr']['data-toggle'] = 'date';
                            $opts['data']                = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d') : null;
                        } else {
                            $opts['attr']['data-toggle'] = 'time';
                            // $opts['with_seconds']   = true; // @todo figure out why this cause the contact form to fail.
                            $opts['data']          = (!empty($value)) ? $dtHelper->toLocalString('H:i:s') : null;
                        }

                        $builder->addEventListener(
                            FormEvents::PRE_SUBMIT,
                            function (FormEvent $event) use ($alias, $type): void {
                                $data = $event->getData();

                                if (!empty($data[$alias])) {
                                    if (false === ($timestamp = strtotime($data[$alias]))) {
                                        $timestamp = null;
                                    }
                                    if ($timestamp) {
                                        $dtHelper = new DateTimeHelper(date('Y-m-d H:i:s', $timestamp), null, 'local');
                                        switch ($type) {
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
                    }

                    $builder->add($alias, $type, $opts);
                    break;
                case SelectType::class:
                case MultiselectType::class:
                case BooleanType::class:
                    if (MultiselectType::class === $type) {
                        $constraints[] = new Length(['max' => 65535]);
                    }

                    $typeProperties = [
                        'required'    => $required,
                        'label'       => $field['label'],
                        'attr'        => $attr,
                        'mapped'      => $mapped,
                        'constraints' => $constraints,
                    ];

                    $emptyValue = '';
                    if (in_array($type, [SelectType::class, MultiselectType::class]) && !empty($properties['list'])) {
                        $typeProperties['choices']      = array_flip(FormFieldHelper::parseList($properties['list']));
                        $cleaningRules[$field['alias']] = 'raw';
                    }
                    if (BooleanType::class === $type && !empty($properties['yes']) && !empty($properties['no'])) {
                        $typeProperties['yes_label'] = $properties['yes'];
                        $typeProperties['no_label']  = $properties['no'];
                        $emptyValue                  = ' x ';
                        if ('' !== $value && null !== $value) {
                            $value = (int) $value;
                        }
                    }

                    $typeProperties['data']        = MultiselectType::class === $type ? FormFieldHelper::parseList($value) : $value;
                    $typeProperties['placeholder'] = $emptyValue;
                    $builder->add(
                        $alias,
                        $type,
                        $typeProperties
                    );
                    break;
                case CountryType::class:
                case RegionType::class:
                case TimezoneType::class:
                case LocaleType::class:
                    $builder->add(
                        $alias,
                        $type,
                        [
                            'required'          => $required,
                            'label'             => $field['label'],
                            'data'              => $value,
                            'attr'              => [
                                'class'            => 'form-control',
                                'data-placeholder' => $field['label'],
                            ],
                            'mapped'      => $mapped,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
                default:
                    $attr['data-encoding'] = 'raw';
                    switch ($type) {
                        case LookupType::class:
                            $attr['data-target'] = $alias;
                            $constraints[]       = new Length(['max' => 191]);
                            if (!empty($properties['list'])) {
                                $attr['data-options'] = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, array_keys(FormFieldHelper::parseList($properties['list'])));
                            }
                            break;
                        case EmailType::class:
                            // Enforce a valid email
                            $attr['data-encoding'] = 'email';
                            $constraints[]         = new EmailAddress();
                            break;
                        case TextType::class:
                            $constraints[] = new Length(['max' => 191]);
                            break;

                        case MultiselectType::class:
                            $constraints[] = new Length(['max' => 65535]);
                            break;

                        case TextareaType::class:
                            if (!empty($properties['allowHtml'])) {
                                $cleaningRules[$field['alias']] = 'html';
                            }
                            break;
                    }

                    $builder->add(
                        $alias,
                        $type,
                        [
                            'required'    => $field['isRequired'],
                            'label'       => $field['label'],
                            'label_attr'  => ['class' => 'control-label'],
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
