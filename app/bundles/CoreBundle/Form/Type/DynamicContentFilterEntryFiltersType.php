<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class DynamicContentFilterEntryFiltersType.
 */
class DynamicContentFilterEntryFiltersType extends AbstractType
{
    private $operatorChoices = [];
    private $translator;

    /**
     * DynamicContentFilterEntryFiltersType constructor.
     *
     * @param MauticFactory $factory
     * @param ListModel     $listModel
     */
    public function __construct(MauticFactory $factory, ListModel $listModel)
    {
        $operatorChoices = $listModel->getFilterExpressionFunctions();

        foreach ($operatorChoices as $key => $value) {
            if (empty($value['hide'])) {
                $this->operatorChoices[$key] = $value['label'];
            }
        }

        $this->translator = $factory->getTranslator();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'glue',
            'choice',
            [
                'label'   => false,
                'choices' => [
                    'and' => 'mautic.lead.list.form.glue.and',
                    'or'  => 'mautic.lead.list.form.glue.or',
                ],
                'attr' => [
                    'class'    => 'form-control not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)',
                ],
            ]
        );

        $translator      = $this->translator;
        $operatorChoices = $this->operatorChoices;

        $formModifier = function (FormEvent $event, $eventName) use ($translator, $operatorChoices) {
            $data    = $event->getData();
            $form    = $event->getForm();
            $options = $form->getConfig()->getOptions();

            $fieldType   = $data['type'];
            $fieldName   = $data['field'];
            $fieldObject = $data['object'];

            $type = 'text';
            $attr = [
                'class' => 'form-control',
            ];
            $displayType = 'hidden';
            $displayAttr = [];

            $customOptions = [];

            switch ($fieldType) {
                case 'country':
                case 'region':
                case 'timezone':
                case 'stage':
                case 'locale':
                    switch ($fieldType) {
                        case 'country':
                            $choiceKey = 'countries';
                            break;
                        case 'region':
                            $choiceKey = 'regions';
                            break;
                        case 'timezone':
                            $choiceKey = 'timezones';
                            break;
                        case 'stage':
                            $choiceKey = 'stages';
                            break;
                        case 'locale':
                            $choiceKey = 'locales';
                            break;
                    }

                    $type                     = 'choice';
                    $customOptions['choices'] = $options[$choiceKey];

                    $customOptions['multiple'] = (in_array($data['operator'], ['in', '!in']));

                    if ($customOptions['multiple']) {
                        array_unshift($customOptions['choices'], ['' => '']);

                        if (!isset($data['filter'])) {
                            $data['filter'] = [];
                        }
                    }

                    break;
                case 'time':
                case 'date':
                case 'datetime':
                    $attr['data-toggle'] = $fieldType;
                    break;
                case 'lookup_id':
                    $type        = 'hidden';
                    $displayType = 'text';
                    $displayAttr = array_merge(
                        $displayAttr,
                        [
                            'class'       => 'form-control',
                            'data-toggle' => 'field-lookup',
                            'data-action' => 'lead:fieldList',
                            'data-target' => $data['field'],
                            'placeholder' => $translator->trans(
                                'mautic.lead.list.form.filtervalue'
                            ),
                        ]
                    );

                    if (isset($options['fields'][$fieldObject][$fieldName]['properties']['list'])) {
                        $displayAttr['data-options'] = $options['fields'][$fieldName]['properties']['list'];
                    }

                    break;
                case 'select':
                case 'multiselect':
                case 'boolean':
                    $type = 'choice';
                    $attr = array_merge(
                        $attr,
                        [
                            'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue'),
                        ]
                    );

                    if (in_array($data['operator'], ['in', '!in'])) {
                        $customOptions['multiple'] = true;
                        if (!isset($data['filter'])) {
                            $data['filter'] = [];
                        } elseif (!is_array($data['filter'])) {
                            $data['filter'] = [$data['filter']];
                        }
                    }

                    $list    = $options['fields'][$fieldObject][$fieldName]['properties']['list'];
                    $choices = FormFieldHelper::parseList($list, true, ('boolean' === $fieldType));

                    if ($fieldType == 'select') {
                        // array_unshift cannot be used because numeric values get lost as keys
                        $choices     = array_reverse($choices, true);
                        $choices[''] = '';
                        $choices     = array_reverse($choices, true);
                    }

                    $customOptions['choices'] = $choices;
                    break;
                case 'lookup':
                default:
                    $attr = array_merge(
                        $attr,
                        [
                            'data-toggle' => 'field-lookup',
                            'data-action' => 'lead:fieldList',
                            'data-target' => $data['field'],
                            'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue'),
                        ]
                    );

                    if (isset($options['fields'][$fieldObject][$fieldName]['properties']['list'])) {
                        $attr['data-options'] = $options['fields'][$fieldName]['properties']['list'];
                    }

                    break;
            }

            if (in_array($data['operator'], ['empty', '!empty'])) {
                $attr['disabled'] = 'disabled';
            } elseif (null !== $data['filter']) {
                $customOptions['constraints'] = [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ];
            }

            // @todo implement in UI
            if (in_array($data['operator'], ['between', '!between'])) {
                $form->add(
                    'filter',
                    'collection',
                    [
                        'type'    => $type,
                        'options' => [
                            'label' => false,
                            'attr'  => $attr,
                        ],
                        'label' => false,
                    ]
                );
            } else {
                $form->add(
                    'filter',
                    $type,
                    array_merge(
                        [
                            'label'          => false,
                            'attr'           => $attr,
                            'data'           => isset($data['filter']) ? $data['filter'] : '',
                            'error_bubbling' => false,
                        ],
                        $customOptions
                    )
                );
            }

            $form->add(
                'display',
                $displayType,
                [
                    'label'          => false,
                    'attr'           => $displayAttr,
                    'data'           => $data['display'],
                    'error_bubbling' => false,
                ]
            );

            $choices = $operatorChoices;
            if (isset($options['fields'][$fieldObject][$fieldName]['operators']['include'])) {
                // Inclusive operators
                $choices = array_intersect_key($choices, array_flip($options['fields'][$fieldObject][$fieldName]['operators']['include']));
            } elseif (isset($options['fields'][$fieldObject][$fieldName]['operators']['exclude'])) {
                // Inclusive operators
                $choices = array_diff_key($choices, array_flip($options['fields'][$fieldObject][$fieldName]['operators']['exclude']));
            }

            $form->add(
                'operator',
                'choice',
                [
                    'label'   => false,
                    'choices' => $choices,
                    'attr'    => [
                        'class'    => 'form-control not-chosen',
                        'onchange' => 'Mautic.convertDynamicContentFilterInput(this)',
                    ],
                ]
            );

            if ($eventName == FormEvents::PRE_SUBMIT) {
                $event->setData($data);
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SET_DATA);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SUBMIT);
            }
        );

        $builder->add('field', 'hidden');
        $builder->add('object', 'hidden');
        $builder->add('type', 'hidden');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            [
                'countries',
                'regions',
                'timezones',
                'stages',
                'locales',
                'fields',
            ]
        );

        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dynamic_content_filter_entry_filters';
    }
}
