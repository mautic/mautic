<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Helper\FormFieldHelper;

/**
 * Class UpdateLeadActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class UpdateLeadActionType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->factory->getModel('lead.field');
        $leadFields = $fieldModel->getEntities(
            array(
                'force'          => array(
                    array(
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true
                    )
                ),
                'hydration_mode' => 'HYDRATE_ARRAY'
            )
        );

        $options['fields'] = $leadFields;
        $options['ignore_required_constraints'] = true;

        // @todo - change the rest of this method to simply use new EntityFieldsBuildFormTrait::getFormFields added by https://github.com/mautic/mautic/pull/2558 after chagnes from LeadType are merged into the trait
        $fieldValues = [];
        $isObject    = false;
        if (!empty($options['data'])) {
            $isObject    = is_object($options['data']);
            $fieldValues = ($isObject) ? $options['data']->getFields() : $options['data'];
        }
        $mapped = !$isObject;

        foreach ($options['fields'] as $field) {
            if ($field['isPublished'] === false) continue;
            $attr        = ['class' => 'form-control'];
            $properties  = $field['properties'];
            $type        = $field['type'];
            $required    = ($isObject) ? $field['isRequired'] : false;
            $alias       = $field['alias'];
            $group       = $field['group'];

            if ($field['isUniqueIdentifer']) {
                $attr['data-unique-identifier'] = $field['alias'];
            }

            if ($isObject) {
                $value       = (isset($fieldValues[$group][$alias]['value'])) ?
                    $fieldValues[$group][$alias]['value'] : $field['defaultValue'];
            } else {
                $value = (isset($fieldValues[$alias])) ? $fieldValues[$alias] : '';
            }

            $constraints = [];
            if ($required && empty($options['ignore_required_constraints'])) {
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
                        $properties['precision'] = (int) $properties['precision'];
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
                            'precision'     => $properties['precision'],
                            'rounding_mode' => isset($properties['roundmode']) ? (int) $properties['roundmode'] : 0
                        ]
                    );
                    break;
                case 'date':
                case 'datetime':
                case 'time':
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
                        'constraints' => $constraints
                    ];

                    if ($value) {
                        try {
                            $dtHelper = new DateTimeHelper($value, null, 'local');
                        } catch (\Exception $e) {
                            // Rather return empty value than break the page
                            $value = null;
                        }
                    }

                    if ($type == 'datetime') {
                        $opts['model_timezone'] = 'UTC';
                        $opts['view_timezone']  = date_default_timezone_get();
                        $opts['format']         = 'yyyy-MM-dd HH:mm';
                        $opts['with_seconds']   = false;

                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d H:i:s') : null;
                    } elseif ($type == 'date') {
                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('Y-m-d') : null;
                    } else {
                        $opts['data'] = (!empty($value)) ? $dtHelper->toLocalString('H:i:s') : null;
                    }

                    $builder->add($alias, $type, $opts);
                    break;
                case 'select':
                case 'multiselect':
                case 'boolean':
                    $typeProperties = [
                        'required'    => $required,
                        'label'       => $field['label'],
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => $attr,
                        'mapped'      => $mapped,
                        'multiple'    => false,
                        'constraints' => $constraints
                    ];

                    $choiceType = 'choice';
                    $emptyValue = '';
                    if (in_array($type, ['select', 'multiselect']) && !empty($properties['list'])) {
                        $typeProperties['choices']  = FormFieldHelper::parseList($properties['list']);
                        $typeProperties['expanded'] = false;
                        $typeProperties['multiple'] = ('multiselect' === $type);
                    }
                    if ($type == 'boolean' && !empty($properties['yes']) && !empty($properties['no'])) {
                        $choiceType              = 'yesno_button_group';
                        $typeProperties['expanded']  = true;
                        $typeProperties['yes_label'] = $properties['yes'];
                        $typeProperties['no_label']  = $properties['no'];
                        $typeProperties['attr']      = [];
                        $emptyValue                  = ' x ';
                        if ($value !== '' && $value !== null) {
                            $value = (int) $value;
                        }
                    }
                    $typeProperties['data'] = $value;
                    $typeProperties['empty_value'] = $emptyValue;
                    $builder->add(
                        $alias,
                        $choiceType,
                        $typeProperties
                    );
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
                            'choices'     => $choices,
                            'required'    => $required,
                            'label'       => $field['label'],
                            'label_attr'  => ['class' => 'control-label'],
                            'data'        => $value,
                            'attr'        => [
                                'class'            => 'form-control',
                                'data-placeholder' => $field['label']
                            ],
                            'mapped'      => $mapped,
                            'multiple'    => false,
                            'expanded'    => false,
                            'constraints' => $constraints
                        ]
                    );
                    break;
                default:
                    if ($type == 'lookup') {
                        $type                = "text";
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
                            'required'    => $field['isRequired'],
                            'label'       => $field['label'],
                            'label_attr'  => ['class' => 'control-label'],
                            'attr'        => $attr,
                            'data'        => $value,
                            'mapped'      => $mapped,
                            'constraints' => $constraints
                        ]
                    );
                    break;
            }
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "updatelead_action";
    }
}