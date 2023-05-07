<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CampaignEventLeadFieldValueType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var FieldModel
     */
    protected $fieldModel;

    public function __construct(TranslatorInterface $translator, LeadModel $leadModel, FieldModel $fieldModel)
    {
        $this->translator = $translator;
        $this->leadModel  = $leadModel;
        $this->fieldModel = $fieldModel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'field',
            LeadFieldsType::class,
            [
                'label'                 => 'mautic.lead.campaign.event.field',
                'label_attr'            => ['class' => 'control-label'],
                'multiple'              => false,
                'with_company_fields'   => true,
                'with_tags'             => true,
                'with_utm'              => true,
                'placeholder'           => 'mautic.core.select',
                'attr'                  => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.lead.campaign.event.field_descr',
                    'onchange' => 'Mautic.updateLeadFieldValues(this)',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) {
            $data = $e->getData();
            $form = $e->getForm();

            $fieldValues = null;
            $fieldType   = null;
            $choiceAttr  = [];
            $operator    = '=';

            if (isset($data['field'])) {
                $field    = $this->fieldModel->getRepository()->findOneBy(['alias' => $data['field']]);
                $operator = $data['operator'];

                if ($field) {
                    $properties = $field->getProperties();
                    $fieldType  = $field->getType();
                    if (!empty($properties['list'])) {
                        // Lookup/Select options
                        $fieldValues = FormFieldHelper::parseList($properties['list']);
                    } elseif (!empty($properties) && 'boolean' == $fieldType) {
                        // Boolean options
                        $fieldValues = [
                            0 => $properties['no'],
                            1 => $properties['yes'],
                        ];
                    } else {
                        switch ($fieldType) {
                            case 'country':
                                $fieldValues = FormFieldHelper::getCountryChoices();
                                break;
                            case 'region':
                                $fieldValues = FormFieldHelper::getRegionChoices();
                                break;
                            case 'timezone':
                                $fieldValues = FormFieldHelper::getTimezonesChoices();
                                break;
                            case 'locale':
                                // Locales are flipped. And yes, we will flip the array again below.
                                $fieldValues = array_flip(FormFieldHelper::getLocaleChoices());
                                break;
                            case 'date':
                            case 'datetime':
                                if ('date' === $operator) {
                                    $fieldHelper = new FormFieldHelper();
                                    $fieldHelper->setTranslator($this->translator);
                                    $fieldValues = $fieldHelper->getDateChoices();
                                    $customText  = $this->translator->trans('mautic.campaign.event.timed.choice.custom');
                                    $customValue = (empty($data['value']) || isset($fieldValues[$data['value']])) ? 'custom' : $data['value'];
                                    $fieldValues = array_merge(
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
                                break;
                            case 'boolean':
                            case 'lookup':
                            case 'select':
                            case 'radio':
                                if (!empty($properties)) {
                                    $fieldValues = $properties;
                                }
                        }
                    }
                }
            }

            $supportsValue   = !in_array($operator, ['empty', '!empty']);
            $supportsChoices = !in_array($operator, ['empty', '!empty', 'regexp', '!regexp']);

            // Display selectbox for a field with choices, textbox for others
            if (!empty($fieldValues) && $supportsChoices) {
                $form->add(
                    'value',
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($fieldValues),
                        'label'             => 'mautic.form.field.form.value',
                        'label_attr'        => ['class' => 'control-label'],
                        'attr'              => [
                            'class'                => 'form-control',
                            'onchange'             => 'Mautic.updateLeadFieldValueOptions(this)',
                            'data-toggle'          => $fieldType,
                            'data-onload-callback' => 'updateLeadFieldValueOptions',
                        ],
                        'choice_attr' => $choiceAttr,
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                    ]
                );
            } else {
                $attr = [
                    'class'                => 'form-control',
                    'data-toggle'          => $fieldType,
                    'data-onload-callback' => 'updateLeadFieldValueOptions',
                ];

                if (!$supportsValue) {
                    $attr['disabled'] = 'disabled';
                }

                $form->add(
                    'value',
                    TextType::class,
                    [
                        'label'       => 'mautic.form.field.form.value',
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => $attr,
                        'constraints' => ($supportsValue) ? [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ] : [],
                    ]
                );
            }

            $form->add(
                'operator',
                ChoiceType::class,
                [
                    'choices'           => $this->leadModel->getOperatorsForFieldType(null == $fieldType ? 'default' : $fieldType, ['date']),
                    'label'             => 'mautic.lead.lead.submitaction.operator',
                    'label_attr'        => ['class' => 'control-label'],
                    'attr'              => [
                        'onchange' => 'Mautic.updateLeadFieldValues(this)',
                    ],
                ]
            );
        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'campaignevent_lead_field_value';
    }
}
