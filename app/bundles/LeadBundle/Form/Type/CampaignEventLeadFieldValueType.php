<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\AbsoluteRelativeDateFilterType;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class CampaignEventLeadFieldValueType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private FieldModel $fieldModel,
        private TypeOperatorProviderInterface $typeOperatorProvider
    ) {
        $this->typeOperatorProvider->setContext('campaign');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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

        $func = function (FormEvent $e): void {
            $data        = $e->getData();
            $form        = $e->getForm();
            $fieldValues = null;
            $fieldType   = '';
            $choiceAttr  = [];
            $operator    = $data['operator'] ?? '=';

            if (isset($data['field'])) {
                list($fieldType, $fieldValues, $choiceAttr) = $this->getFieldData($data, $operator);
            }

            $supportsValue   = !in_array($operator, ['empty', '!empty']);
            $supportsChoices = !in_array($operator, ['empty', '!empty', 'regexp', '!regexp']);

            // Display selectbox for a field with choices, textbox for others
            if (!empty($fieldValues) && $supportsChoices) {
                $multiple = in_array($operator, ['in', '!in']);
                $value    = $multiple && !is_array($data['value']) ? [$data['value']] : $data['value'];

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
                        'multiple' => $multiple,
                        'data'     => $value,
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
                    'choices'    => $this->typeOperatorProvider->getOperatorsForFieldType($fieldType, ['date']),
                    'label'      => 'mautic.lead.lead.submitaction.operator',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'onchange' => 'Mautic.updateLeadFieldValues(this)',
                    ],
                ]
            );
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    public function getBlockPrefix(): string
    {
        return 'campaignevent_lead_field_value';
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function getFieldData(array $data, string $operator): array
    {
        $fieldType   = '';
        $choiceAttr  = [];
        $fieldValues = null;

        $field = $this->fieldModel->getRepository()->findOneBy(['alias' => $data['field']]);
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
                        $fieldValues = ArrayHelper::flatten(FormFieldHelper::getRegionChoices());
                        break;
                    case 'timezone':
                        $fieldValues = ArrayHelper::flatten(FormFieldHelper::getTimezonesChoices());
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
                            $customValue = (empty($data['value']) || isset($fieldValues[$data['value']]))
                                            ? 'custom'
                                            : $data['value'];
                            $fieldValues = array_merge(
                                [
                                    $customValue => $customText,
                                ],
                                $fieldValues
                            );

                            $choiceAttr = function ($value) use ($customValue) {
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
                        break;
                    default:
                }
            }
        }

        return [$fieldType, $fieldValues, $choiceAttr];
    }
}
