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

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignEventFormSubmitType.
 */
class CampaignEventFormFieldValueType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * CampaignEventFormFieldValueType constructor.
     *
     * @param TranslatorInterface $translator
     * @param FormModel           $formModel
     */
    public function __construct(TranslatorInterface $translator, FormModel $formModel)
    {
        $this->translator          = $translator;
        $this->formModel           = $formModel;
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

        $formModel = $this->formModel;
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
                'attr'    => [
                    'onchange' => 'Mautic.updateFormOperators(this)',
                ],
            ]
        );

        $ff = $builder->getFormFactory();

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) use ($ff, $formModel) {
            $data        = $e->getData();
            $form        = $e->getForm();
            $fields      = [];
            $fieldTypes  = [];
            $options     = [];

            if ($form->has('field')) {
                $form->remove('field');
            }

            if (empty($data['form'])) {
                $fields[] = 'Select form first';
            } else {
                $formEntity = $formModel->getEntity($data['form']);
                $formFields = $formEntity->getFields();

                /** @var Field $field * */
                foreach ($formFields as $field) {
                    if ($field->getType() != 'button') {
                        $fields[$field->getAlias()]      = $field->getLabel();
                        $fieldTypes[$field->getAlias()]  = $field->getType();
                        $options[$field->getAlias()]     = [];
                        $properties                      = $field->getProperties();

                        if (!empty($properties['list']['list'])) {
                            $options[$field->getAlias()] = [];
                            foreach ($properties['list']['list'] as $option) {
                                if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                                    //The select box needs values to be [value] => label format so make sure we have that style then put it in
                                    $options[$field->getAlias()][$option['value']] = $option['label'];
                                } elseif (!is_array($option)) {
                                    //Kept here for BC
                                    $options[$field->getAlias()][$option] = $option;
                                }
                            }
                        } elseif (in_array($field->getType(), ['date', 'datetime'])) {
                            $fieldHelper = new \Mautic\LeadBundle\Helper\FormFieldHelper();
                            $fieldHelper->setTranslator($this->translator);
                            $fieldValues                 = $fieldHelper->getDateChoices();
                            $customText                  = $this->translator->trans('mautic.campaign.event.timed.choice.custom');
                            $customValue                 = (empty($data['value']) || isset($fieldValues[$data['value']])) ? 'custom' : $data['value'];
                            $options[$field->getAlias()] = array_merge(
                                    [
                                        $customValue => $customText,
                                    ],
                                    $fieldValues
                                );

                            $choiceAttr = function ($value, $key, $index) use ($customValue) {
                                if ($customValue === $value) {
                                    return ['data-custom' => 1];
                                }

                                return [];
                            };
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
                        'data-field-types'   => json_encode($fieldTypes),
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
                            'class'                => 'form-control',
                            'data-onload-callback' => 'updateLeadFieldValueOptions',
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
                            'class'                => 'form-control not-chosen',
                            'onchange'             => 'Mautic.updateLeadFieldValueOptions(this, true)',
                            'data-onload-callback' => 'updateLeadFieldValueOptions',
                        ],
                        'required'    => true,
                        'choice_attr' => $choiceAttr,
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
