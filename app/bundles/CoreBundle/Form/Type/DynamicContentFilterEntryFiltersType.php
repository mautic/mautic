<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class DynamicContentFilterEntryFiltersType
 *
 * @package Mautic\CoreBundle\Form\Type
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
            array(
                'label'   => false,
                'choices' => array(
                    'and' => 'mautic.lead.list.form.glue.and',
                    'or'  => 'mautic.lead.list.form.glue.or'
                ),
                'attr'    => array(
                    'class' => 'form-control not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)'
                )
            )
        );

        $translator      = $this->translator;
        $operatorChoices = $this->operatorChoices;

        $formModifier = function (FormEvent $event, $eventName) use ($translator, $operatorChoices) {
            $data      = $event->getData();
            $form      = $event->getForm();
            $options   = $form->getConfig()->getOptions();

            $fieldType = $data['type'];
            $fieldName = $data['field'];

            $type        = 'text';
            $attr        = array(
                'class' => 'form-control'
            );
            $displayType = 'hidden';
            $displayAttr = array();

            $customOptions = array();

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

                    $customOptions['multiple'] = (in_array($data['operator'], array('in', '!in')));

                    if ($customOptions['multiple']) {
                        array_unshift($customOptions['choices'], array('' => ''));

                        if (!isset($data['filter'])) {
                            $data['filter'] = array();
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
                        array(
                            'class'       => 'form-control',
                            'data-toggle' => 'field-lookup',
                            'data-target' => $data['field'],
                            'placeholder' => $translator->trans(
                                'mautic.lead.list.form.filtervalue'
                            )
                        )
                    );

                    if (isset($options['fields'][$fieldName]['properties']['list'])) {
                        $displayAttr['data-options'] = $options['fields'][$fieldName]['properties']['list'];
                    }

                    break;
                case 'select':
                case 'boolean':
                    $type = 'choice';
                    $attr = array_merge(
                        $attr,
                        array(
                            'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue')
                        )
                    );

                    if (in_array($data['operator'], array('in', '!in'))) {
                        $customOptions['multiple'] = true;
                        if (!isset($data['filter'])) {
                            $data['filter'] = array();
                        } elseif (!is_array($data['filter'])) {
                            $data['filter'] = array($data['filter']);
                        }
                    }

                    $list = $options['fields'][$fieldName]['properties']['list'];
                    $choices = FormFieldHelper::parseListStringIntoArray($list);

                    if ($fieldType == 'select') {
                        // array_unshift cannot be used because numeric values get lost as keys
                        $choices = array_reverse($choices, true);
                        $choices[''] = '';
                        $choices = array_reverse($choices, true);
                    }

                    $customOptions['choices'] = $choices;
                    break;
                case 'lookup':
                default:
                    $attr = array_merge(
                        $attr,
                        array(
                            'data-toggle' => 'field-lookup',
                            'data-target' => $data['field'],
                            'placeholder' => $translator->trans('mautic.lead.list.form.filtervalue')
                        )
                    );

                    if (isset($options['fields'][$fieldName]['properties']['list'])) {
                        $attr['data-options'] = $options['fields'][$fieldName]['properties']['list'];
                    }

                    break;
            }

            if (in_array($data['operator'], array('empty', '!empty'))) {
                $attr['disabled'] = 'disabled';
            } else {
                $customOptions['constraints'] = array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                );
            }

            // @todo implement in UI
            if (in_array($data['operator'], array('between', '!between'))) {
                $form->add(
                    'filter',
                    'collection',
                    array(
                        'type'    => $type,
                        'options' => array(
                            'label' => false,
                            'attr'  => $attr
                        ),
                        'label'   => false
                    )
                );
            } else {
                $form->add(
                    'filter',
                    $type,
                    array_merge(
                        array(
                            'label'          => false,
                            'attr'           => $attr,
                            'data'           => isset($data['filter']) ? $data['filter'] : '',
                            'error_bubbling' => false,
                        ),
                        $customOptions
                    )
                );
            }

            $form->add(
                'display',
                $displayType,
                array(
                    'label'          => false,
                    'attr'           => $displayAttr,
                    'data'           => $data['display'],
                    'error_bubbling' => false
                )
            );

            $choices = $operatorChoices;
            if (isset($options['fields'][$fieldName]['operators']['include'])) {
                // Inclusive operators
                $choices = array_intersect_key($choices, array_flip($options['fields'][$fieldName]['operators']['include']));
            } elseif (isset($options['fields'][$fieldName]['operators']['exclude'])) {
                // Inclusive operators
                $choices = array_diff_key($choices, array_flip($options['fields'][$fieldName]['operators']['exclude']));
            }

            $form->add(
                'operator',
                'choice',
                array(
                    'label'   => false,
                    'choices' => $choices,
                    'attr'    => array(
                        'class'    => 'form-control not-chosen',
                        'onchange' => 'Mautic.convertLeadFilterInput(this)'
                    )
                )
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
                'locales'
            ]
        );

        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "dynamic_content_filter_entry_filters";
    }
}