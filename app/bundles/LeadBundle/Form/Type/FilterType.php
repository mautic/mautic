<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FilterType.
 */
class FilterType extends AbstractType
{
    private $translator;
    private $currentListId;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator    = $factory->getTranslator();
        $this->currentListId = $factory->getRequest()->attributes->get('objectId', false);
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

        $translator    = $this->translator;
        $currentListId = $this->currentListId;

        $formModifier = function (FormEvent $event, $eventName) use ($translator, $currentListId) {
            $data      = $event->getData();
            $form      = $event->getForm();
            $options   = $form->getConfig()->getOptions();
            $fieldType = $data['type'];
            $fieldName = $data['field'];

            $type = 'text';
            $attr = [
                'class' => 'form-control',
            ];
            $displayType = 'hidden';
            $displayAttr = [];

            $field = [];

            if (isset($options['fields']['lead'][$fieldName])) {
                $field = $options['fields']['lead'][$fieldName];
            } elseif (isset($options['fields']['company'][$fieldName])) {
                $field = $options['fields']['company'][$fieldName];
            }

            $customOptions = [];
            switch ($fieldType) {
                case 'leadlist':
                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    } elseif (!is_array($data['filter'])) {
                        $data['filter'] = [$data['filter']];
                    }

                    // Don't show the current list ID in the choices
                    if (!empty($currentListId)) {
                        unset($options['lists'][$currentListId]);
                    }

                    $customOptions['choices']  = $options['lists'];
                    $customOptions['multiple'] = true;
                    $type                      = 'choice';
                    break;
                case 'lead_email_received':
                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    } elseif (!is_array($data['filter'])) {
                        $data['filter'] = [$data['filter']];
                    }

                    $customOptions['choices']  = $options['emails'];
                    $customOptions['multiple'] = true;
                    $type                      = 'choice';
                    break;
                case 'tags':
                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    } elseif (!is_array($data['filter'])) {
                        $data['filter'] = [$data['filter']];
                    }
                    $customOptions['choices']  = $options['tags'];
                    $customOptions['multiple'] = true;
                    $attr                      = array_merge(
                        $attr,
                        [
                            'data-placeholder'     => $translator->trans('mautic.lead.tags.select_or_create'),
                            'data-no-results-text' => $translator->trans('mautic.lead.tags.enter_to_create'),
                            'data-allow-add'       => 'true',
                            'onchange'             => 'Mautic.createLeadTag(this)',
                        ]
                    );
                    $type = 'choice';
                    break;
                case 'stage':
                    $customOptions['choices'] = $options['stage'];
                    $type                     = 'choice';
                    break;
                case 'globalcategory':
                    if (!isset($data['filter'])) {
                        $data['filter'] = [];
                    } elseif (!is_array($data['filter'])) {
                        $data['filter'] = [$data['filter']];
                    }
                    $customOptions['choices']  = $options['globalcategory'];
                    $customOptions['multiple'] = true;
                    $type                      = 'choice';
                    break;
                case 'timezone':
                case 'country':
                case 'region':
                case 'locale':
                    switch ($fieldType) {
                        case 'timezone':
                            $choiceKey = 'timezones';
                            break;
                        case 'country':
                            $choiceKey = 'countries';
                            break;
                        case 'region':
                            $choiceKey = 'regions';
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
                            'data-target' => $data['field'],
                            'data-action' => 'lead:fieldList',
                            'placeholder' => $translator->trans(
                                'mautic.lead.list.form.filtervalue'
                            ),
                        ]
                    );

                    if (isset($field['properties']['list'])) {
                        $displayAttr['data-options'] = $field['properties']['list'];
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

                    $choices = [];
                    if (!empty($field['properties']['list'])) {
                        $list    = $field['properties']['list'];
                        $choices = FormFieldHelper::parseList($list, true, ('boolean' === $fieldType));
                    }

                    if ('select' == $fieldType) {
                        // array_unshift cannot be used because numeric values get lost as keys
                        $choices     = array_reverse($choices, true);
                        $choices[''] = '';
                        $choices     = array_reverse($choices, true);
                    }

                    $customOptions['choices'] = $choices;
                    break;
                case 'lookup':
                default:
                    if ('number' !== $fieldType) {
                        $attr = array_merge(
                            $attr,
                            [
                                'data-toggle' => 'field-lookup',
                                'data-target' => $data['field'],
                                'data-action' => 'lead:fieldList',
                                'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue'),
                            ]
                        );

                        if (isset($field['properties']['list'])) {
                            $attr['data-options'] = $field['properties']['list'];
                        }
                    }

                    break;
            }

            if (in_array($data['operator'], ['empty', '!empty'])) {
                $attr['disabled'] = 'disabled';
            } else {
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

            $form->add(
                'operator',
                'choice',
                [
                    'label'   => false,
                    'choices' => isset($field['operators']) ? $field['operators'] : [],
                    'attr'    => [
                        'class'    => 'form-control not-chosen',
                        'onchange' => 'Mautic.convertLeadFilterInput(this)',
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
                'timezones',
                'countries',
                'regions',
                'fields',
                'lists',
                'emails',
                'tags',
                'stage',
                'locales',
                'globalcategory',
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
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields'] = $options['fields'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadlist_filter';
    }
}
