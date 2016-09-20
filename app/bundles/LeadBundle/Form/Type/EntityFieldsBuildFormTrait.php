<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Validator\Constraints\NotBlank;

trait EntityFieldsBuildFormTrait
{

    private function getFormFields(FormBuilderInterface $builder, array $options, $object =  'lead')
    {
        $fieldValues = (!empty($options['data'])) ? $options['data']->getFields() : ['filter' => ['isVisible' => true, 'object' => $object]];
        foreach ($options['fields'] as $field) {
            if ($field['isPublished'] === false || $field['object'] !== 'company') continue;
            $attr = ['class' => 'form-control'];
            $properties = $field['properties'];
            $type = $field['type'];
            $required = $field['isRequired'];
            $alias = $field['alias'];
            $group = $field['group'];

            if ($field['isUniqueIdentifer']) {
                $attr['data-unique-identifier'] = $field['alias'];
            }

            $value = (isset($fieldValues[$group][$alias]['value'])) ?
                $fieldValues[$group][$alias]['value'] : $field['defaultValue'];
            $constraints = [];
            if ($required) {
                $constraints[] = new NotBlank(
                    ['message' => 'mautic.lead.customfield.notblank']
                );
            }

            switch ($type) {
                case 'number':
                    if (empty($properties['precision'])) {
                        $properties['precision'] = null;
                    } //ensure default locale is used
                    else {
                        $properties['precision'] = (int)$properties['precision'];
                    }

                    $builder->add(
                        $alias,
                        $type,
                        [
                            'required' => $required,
                            'label' => $field['label'],
                            'label_attr' => ['class' => 'control-label'],
                            'attr' => $attr,
                            'data' => (isset($fieldValues[$group][$alias]['value'])) ?
                                (float)$fieldValues[$group][$alias]['value'] : (float)$field['defaultValue'],
                            'mapped' => false,
                            'constraints' => $constraints,
                            'precision' => $properties['precision'],
                            'rounding_mode' => isset($properties['roundmode']) ? (int)$properties['roundmode'] : 0
                        ]
                    );
                    break;
                case 'date':
                case 'datetime':
                case 'time':
                    $attr['data-toggle'] = $type;
                    $opts = [
                        'required' => $required,
                        'label' => $field['label'],
                        'label_attr' => ['class' => 'control-label'],
                        'widget' => 'single_text',
                        'attr' => $attr,
                        'mapped' => false,
                        'input' => 'string',
                        'html5' => false,
                        'constraints' => $constraints
                    ];

                    try {
                        $dtHelper = new DateTimeHelper($value, null, 'local');
                    } catch (\Exception $e) {
// Rather return empty value than break the page
                        $value = '';
                    }

                    if ($type == 'datetime') {
                        $opts['model_timezone'] = 'UTC';
                        $opts['view_timezone'] = date_default_timezone_get();
                        $opts['format'] = 'yyyy-MM-dd HH:mm';
                        $opts['with_seconds'] = false;

                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d H:i:s') : null;
                    } elseif ($type == 'date') {
                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d') : null;
                    } else {
                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('H:i:s') : null;
                    }

                    $builder->add($alias, $type, $opts);
                    break;
                case 'select':
                case 'boolean':
                    $choices = [];
                    if ($type == 'select' && !empty($properties['list'])) {
                        $list = explode('|', $properties['list']);
                        foreach ($list as $l) {
                            $l = trim($l);
                            $choices[$l] = $l;
                        }
                        $expanded = false;
                    }
                    if ($type == 'boolean' && !empty($properties['yes']) && !empty($properties['no'])) {
                        $expanded = true;
                        $choices = [1 => $properties['yes'], 0 => $properties['no']];
                        $attr = [];
                    }

                    if (!empty($choices)) {
                        $builder->add(
                            $alias,
                            'choice',
                            [
                                'choices' => $choices,
                                'required' => $required,
                                'label' => $field['label'],
                                'label_attr' => ['class' => 'control-label'],
                                'data' => ($type == 'boolean') ? (int)$value : $value,
                                'attr' => $attr,
                                'mapped' => false,
                                'multiple' => false,
                                'empty_value' => false,
                                'expanded' => $expanded,
                                'constraints' => $constraints
                            ]
                        );
                    }
                    break;
                case 'country':
                case 'region':
                case 'timezone':
                case 'locale':
                    switch ($type) {
                        case 'country':
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
                        'choice',
                        [
                            'choices' => $choices,
                            'required' => $required,
                            'label' => $field['label'],
                            'label_attr' => ['class' => 'control-label'],
                            'data' => $value,
                            'attr' => [
                                'class' => 'form-control',
                                'data-placeholder' => $field['label']
                            ],
                            'mapped' => false,
                            'multiple' => false,
                            'expanded' => false,
                            'constraints' => $constraints
                        ]
                    );
                    break;
                default:
                    if ($type == 'lookup') {
                        $type = "text";
                        $attr['data-toggle'] = 'field-lookup';
                        $attr['data-target'] = $alias;

                        if (!empty($properties['list'])) {
                            $attr['data-options'] = $properties['list'];
                        }
                    }
                    $builder->add(
                        $alias,
                        $type,
                        [
                            'required' => $field['isRequired'],
                            'label' => $field['label'],
                            'label_attr' => ['class' => 'control-label'],
                            'attr' => $attr,
                            'data' => $value,
                            'mapped' => false,
                            'constraints' => $constraints
                        ]
                    );
                    break;
            }
        }
    }
}