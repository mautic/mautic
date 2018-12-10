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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignEventFormSubmitType.
 */
class CampaignEventFormFieldValueType extends AbstractType
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'form',
            'form_list',
            [
                'label'       => 'mautic.form.campaign.event.forms',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.form.campaign.event.forms_descr',
                    'onchange' => 'Mautic.updateFormFields(this)',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $formModel = $this->factory->getModel('form.form');
        $operators = $formModel->getFilterExpressionFunctions();
        $choices   = [];

        foreach ($operators as $key => $operator) {
            $choices[$key] = $operator['label'];
        }

        $builder->add(
            'operator',
            'choice',
            [
                'choices' => $choices,
            ]
        );

        $ff = $builder->getFormFactory();

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) use ($formModel) {
            $data    = $e->getData();
            $form    = $e->getForm();
            $fields  = [];
            $options = [];

            if ($form->has('field')) {
                $form->remove('field');
            }

            if (empty($data['form'])) {
                $fields[] = 'Select form first';
            } else {
                $formEntity = $formModel->getEntity($data['form']);
                $formFields = $formEntity->getFields();

                foreach ($formFields as $field) {
                    if ($field->getType() != 'button') {
                        $fields[$field->getAlias()]  = $field->getLabel();
                        $options[$field->getAlias()] = [];
                        $properties                  = $field->getProperties();
                        $list                        = [];
                        if (!empty($properties['list']['list'])) {
                            $list = $properties['list']['list'];
                        } elseif (!empty($properties['optionlist']['list'])) {
                            $list =$properties['optionlist']['list'];
                        }

                        if (!empty($list)) {
                            $options[$field->getAlias()] = [];
                            foreach ($list as $option) {
                                if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                                    //The select box needs values to be [value] => label format so make sure we have that style then put it in
                                    $options[$field->getAlias()][$option['value']] = $option['label'];
                                } elseif (is_array($option)) {
                                    foreach ($option as $optgroup => $option) {
                                        $options[$field->getAlias()][$option] = $option;
                                    }
                                } elseif (!is_array($option)) {
                                    //Kept here for BC
                                    $options[$field->getAlias()][$option] = $option;
                                }
                            }
                        }
                    }
                }
            }

            $form->add(
                'field',
                'choice',
                [
                    'choices' => $fields,
                    'attr'    => [
                        'onchange'           => 'Mautic.updateFormFieldValues(this)',
                        'data-field-options' => json_encode($options),
                    ],
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.value.required']
                        ),
                    ],
                ]
            );

            // Display selectbox for a field with choices, textbox for others
            if (empty($data['field']) || empty($options[$data['field']])) {
                $form->add(
                    'value',
                    'text',
                    [
                        'label'      => 'mautic.form.field.form.value',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                    ]
                );
            } else {
                $form->add(
                    'value',
                    'choice',
                    [
                        'choices'    => $options[$data['field']],
                        'label'      => 'mautic.form.field.form.value',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control not-chosen',
                        ],
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                    ]
                );
            }
        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignevent_form_field_value';
    }
}
