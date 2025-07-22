<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\Helper\IndexHelper;
use Mautic\LeadBundle\Field\IdentifierFields;
use Mautic\LeadBundle\Form\DataTransformer\FieldToOrderTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<LeadField>
 */
class FieldType extends AbstractType
{
    /**
     * For which types will be character limits applicable.
     *
     * @var array<string>
     */
    private array $indexableFieldsWithLimits = [
        'text',
        'select',
        'phone',
        'url',
        'email',
    ];

    /**
     * @var string[]
     */
    private static array $fieldsWithNoLengthLimit = [
        'textarea',
        'html',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private Translator $translator,
        private IdentifierFields $identifierFields,
        private IndexHelper $indexHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new FormExitSubscriber('lead.field', $options));

        $builder->add(
            'label',
            TextType::class,
            [
                'label'      => 'mautic.lead.field.label',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control', 'length' => 191],
            ]
        );

        $disabled = (!empty($options['data'])) ? $options['data']->isFixed() : false;

        $builder->add(
            'group',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.lead.field.group.core'         => 'core',
                    'mautic.lead.field.group.social'       => 'social',
                    'mautic.lead.field.group.personal'     => 'personal',
                    'mautic.lead.field.group.professional' => 'professional',
                ],
                'attr' => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.lead.field.form.group.help',
                    'onchange' => 'Mautic.updateLeadFieldOrderChoiceList();',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.lead.field.group',
                'placeholder' => false,
                'required'    => false,
                'disabled'    => $disabled,
            ]
        );

        $new         = $options['data']->getId() ? false : true;
        $type        = $options['data']->getType();
        $isIndex     = $options['data']->isIsIndex();
        $default     = (empty($type)) ? 'text' : $type;
        $fieldHelper = new FormFieldHelper();
        $fieldHelper->setTranslator($this->translator);

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'choices'     => $fieldHelper->getChoiceList(),
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.lead.field.type',
                'placeholder' => false,
                'disabled'    => ($disabled || !$new),
                'attr'        => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.updateLeadFieldProperties(this.value);',
                ],
                'data'     => $default,
                'required' => false,
            ]
        );

        $builder->add(
            'properties_select_template',
            SortableListType::class,
            [
                'mapped'          => false,
                'label'           => 'mautic.lead.field.form.properties.select',
                'option_required' => false,
                'with_labels'     => true,
            ]
        );

        $builder->add(
            'properties_lookup_template',
            SortableListType::class,
            [
                'mapped'          => false,
                'label'           => 'mautic.lead.field.form.properties.select',
                'option_required' => false,
                'with_labels'     => false,
            ]
        );

        $listChoices = [
            'country'       => FormFieldHelper::getCountryChoices(),
            'region'        => FormFieldHelper::getRegionChoices(),
            'timezone'      => FormFieldHelper::getTimezonesChoices(),
            'locale'        => FormFieldHelper::getLocaleChoices(),
            'select'        => [],
        ];
        foreach ($listChoices as $listType => $choices) {
            $builder->add(
                'default_template_'.$listType,
                ChoiceType::class,
                [
                    'choices'    => $choices,
                    'label'      => 'mautic.core.defaultvalue',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control not-chosen'],
                    'required'   => false,
                    'mapped'     => false,
                ]
            );
        }

        $builder->add(
            'default_template_text',
            TextType::class,
            [
                'label'      => 'mautic.core.defaultvalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'mapped'     => false,
            ]
        );

        $builder->add(
            'default_template_textarea',
            TextareaType::class,
            [
                'label'      => 'mautic.core.defaultvalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'mapped'     => false,
            ]
        );

        $builder->add(
            'default_template_boolean',
            YesNoButtonGroupType::class,
            [
                'label'       => 'mautic.core.defaultvalue',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'mapped'      => false,
                'data'        => 0,
            ]
        );

        $builder->add(
            'properties',
            CollectionType::class,
            [
                'required'       => false,
                'allow_add'      => true,
                'error_bubbling' => false,
            ]
        );

        $disableDefaultValue = (!$new && in_array($options['data']->getAlias(), $this->identifierFields->getFieldList($options['data']->getObject())));
        $builder->add(
            'defaultValue',
            TextType::class,
            [
                'label'      => 'mautic.core.defaultvalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.field.help.defaultvalue',
                ],
                'required'    => false,
                'disabled'    => $disableDefaultValue,
                'constraints' => [
                    new Assert\Callback([$this, 'validateDefaultValue']),
                ],
            ]
        );

        /**
         * @see FormEvents::PRE_SET_DATA
         * Used as as form modifier before trying to set data
         */
        $formModifier = function (FormEvent $event) use ($listChoices, $type, $options, $disableDefaultValue, $new): array {
            $cleaningRules = [];
            $form          = $event->getForm();
            $data          = $event->getData();
            $type          = (is_array($data)) ? ($data['type'] ?? $type) : $data->getType();
            $constraints   = [];

            switch ($type) {
                case 'select':
                case 'lookup':
                    $constraints = new Assert\Callback([$this, 'validateDefaultValue']);
                    // no break
                case 'multiselect':
                    $cleaningRules['defaultValue'] = 'raw';

                    if (is_array($data)) {
                        $properties = $data['properties'] ?? [];
                    } else {
                        $properties = $data->getProperties();
                    }

                    $propertiesList['list'] = isset($properties['list']) && 'lookup' === $type ? array_flip(array_filter($properties['list'])) : $properties['list'];

                    $form->add(
                        'properties',
                        SortableListType::class,
                        [
                            'required'          => false,
                            'label'             => 'mautic.lead.field.form.properties.select',
                            'data'              => $propertiesList,
                            'with_labels'       => ('lookup' !== $type),
                            'option_constraint' => [],
                        ]
                    );

                    $list = isset($properties['list']) ? FormFieldHelper::parseList($properties['list']) : [];
                    $form->add(
                        'defaultValue',
                        ChoiceType::class,
                        [
                            'label'       => 'mautic.core.defaultvalue',
                            'label_attr'  => ['class' => 'control-label is-chosen'],
                            'attr'        => ['class' => 'form-control'],
                            'required'    => false,
                            'choices'     => array_flip($list),
                            'multiple'    => 'multiselect' === $type,
                            'data'        => 'multiselect' === $type && is_string($options['data']->getDefaultValue()) ? explode('|', $options['data']->getDefaultValue()) : $options['data']->getDefaultValue(),
                            'disabled'    => $disableDefaultValue,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
                case 'country':
                case 'locale':
                case 'timezone':
                case 'region':
                    $form->add(
                        'defaultValue',
                        ChoiceType::class,
                        [
                            'choices'    => $listChoices[$type],
                            'label'      => 'mautic.core.defaultvalue',
                            'label_attr' => ['class' => 'control-label'],
                            'attr'       => ['class' => 'form-control'],
                            'required'   => false,
                            'disabled'   => $disableDefaultValue,
                        ]
                    );
                    break;
                case 'boolean':
                    if (is_array($data)) {
                        $value    = $data['defaultValue'] ?? false;
                        $yesLabel = !empty($data['properties']['yes']) ? $data['properties']['yes'] : 'mautic.core.form.yes';
                        $noLabel  = !empty($data['properties']['no']) ? $data['properties']['no'] : 'mautic.core.form.no';
                    } else {
                        $value    = $data->getDefaultValue();
                        $props    = $data->getProperties();
                        $yesLabel = !empty($props['yes']) ? $props['yes'] : 'mautic.core.form.yes';
                        $noLabel  = !empty($props['no']) ? $props['no'] : 'mautic.core.form.no';
                    }

                    if ('' !== $value && null !== $value) {
                        $value = (int) $value;
                    }

                    $form->add(
                        'defaultValue',
                        YesNoButtonGroupType::class,
                        [
                            'label'       => 'mautic.core.defaultvalue',
                            'label_attr'  => ['class' => 'control-label'],
                            'attr'        => ['class' => 'form-control'],
                            'required'    => false,
                            'data'        => $value,
                            'no_label'    => $noLabel,
                            'yes_label'   => $yesLabel,
                            'placeholder' => ' x ',
                        ]
                    );
                    break;
                case 'datetime':
                case 'date':
                case 'time':
                    $constraints = [];
                    switch ($type) {
                        case 'datetime':
                            $constraints = [
                                new Assert\Callback(
                                    function ($object, ExecutionContextInterface $context): void {
                                        if (!empty($object) && false === \DateTime::createFromFormat('Y-m-d H:i', $object)) {
                                            $context->buildViolation('mautic.lead.datetime.invalid')->addViolation();
                                        }
                                    }
                                ),
                            ];
                            break;
                        case 'date':
                            $constraints = [
                                new Assert\Callback(
                                    function ($object, ExecutionContextInterface $context): void {
                                        if (!empty($object)) {
                                            $validator  = $context->getValidator();
                                            $violations = $validator->validate($object, new Assert\Date());

                                            if (count($violations) > 0) {
                                                $context->buildViolation('mautic.lead.date.invalid')->addViolation();
                                            }
                                        }
                                    }
                                ),
                            ];
                            break;
                        case 'time':
                            $constraints = [
                                new Assert\Callback(
                                    function ($object, ExecutionContextInterface $context): void {
                                        if (!empty($object)) {
                                            $validator  = $context->getValidator();
                                            $violations = $validator->validate(
                                                $object,
                                                new Assert\Regex(['pattern' => '/(2[0-3]|[01][0-9]):([0-5][0-9])/'])
                                            );

                                            if (count($violations) > 0) {
                                                $context->buildViolation('mautic.lead.time.invalid')->addViolation();
                                            }
                                        }
                                    }
                                ),
                            ];
                            break;
                    }

                    $form->add(
                        'defaultValue',
                        TextType::class,
                        [
                            'label'       => 'mautic.core.defaultvalue',
                            'label_attr'  => ['class' => 'control-label'],
                            'attr'        => [
                                'class'       => 'form-control',
                                'data-toggle' => $type,
                            ],
                            'required'    => false,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
                case 'tel':
                case 'url':
                case 'email':
                    $constraints = new Assert\Callback([$this, 'validateDefaultValue']);
                    // no break
                case 'number':
                    $form->add(
                        'defaultValue',
                        TextType::class,
                        [
                            'label'      => 'mautic.core.defaultvalue',
                            'label_attr' => ['class' => 'control-label'],
                            'attr'       => [
                                'class' => 'form-control',
                                'type'  => $type,
                            ],
                            'required'    => false,
                            'disabled'    => $disableDefaultValue,
                            'constraints' => $constraints,
                        ]
                    );
                    break;
            }

            if (in_array($type, $this->indexableFieldsWithLimits)) {
                $this->addLengthValidationField($form, $new);
            }

            return $cleaningRules;
        };

        $setupOrderField = function (FormInterface $form, string $object = null, string $group = null) use ($builder, $disabled): void {
            /** @var LeadFieldRepository $leadFieldRepository */
            $leadFieldRepository = $this->em->getRepository(LeadField::class);

            $options = [
                'label'         => 'mautic.core.order.field',
                'class'         => LeadField::class,
                'choice_label'  => 'label',
                'label_attr'    => ['class' => 'control-label'],
                'attr'          => [
                    'class'   => 'form-control',
                    'tooltip' => $disabled ? 'mautic.core.order.field.tooltip.disabled' : 'mautic.core.order.field.tooltip',
                ],
                'required'        => false,
                'auto_initialize' => false,
                'disabled'        => $disabled,
            ];
            // There's no need to filter list during FormEvents::PRE_SUBMIT.
            if ($object && $group) {
                $options['query_builder'] = fn (EntityRepository $er) => $er->createQueryBuilder('f')
                    ->orderBy('f.order', Order::Ascending->value)
                    ->where('f.object = :object')
                    ->setParameter('object', $object)
                    ->andWhere('f.group = :group')
                    ->setParameter('group', $group)
                    ->andWhere('f.isFixed = FALSE');
            }

            // get order list
            $transformer = new FieldToOrderTransformer($leadFieldRepository);
            $form->add(
                $builder->create(
                    'order',
                    EntityType::class,
                    $options,
                )->addModelTransformer($transformer)->getForm()
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier, $setupOrderField): void {
                $formModifier($event);
                /** @var LeadField $field */
                $field = $event->getData();
                $setupOrderField($event->getForm(), $field->getObject(), $field->getGroup());
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier, $disableDefaultValue, $setupOrderField): void {
                $data          = $event->getData();
                $cleaningRules = $formModifier($event);
                $masks         = !empty($cleaningRules) ? $cleaningRules : 'clean';
                // clean the data
                $data = InputHelper::_($data, $masks);

                if ((isset($data['group']) && 'social' === $data['group']) || !empty($data['isUniqueIdentifer']) || $disableDefaultValue) {
                    // Don't allow a default for social or unique identifiers
                    $data['defaultValue'] = null;
                }

                if (isset($data['type']) && !in_array($data['type'], $this->indexableFieldsWithLimits)) {
                    $data['charLengthLimit'] = null;
                }

                $event->setData($data);
                $setupOrderField($event->getForm());
            }
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.field.help.alias',
                ],
                'required'   => false,
                'disabled'   => ($disabled || !$new),
            ]
        );

        $attr = [];
        if ($options['data']->getColumnIsNotCreated()) {
            $attr = [
                'tooltip' => 'mautic.lead.field.being_created_in_background',
            ];
        }

        $builder->add(
            'isPublished',
            YesNoButtonGroupType::class,
            [
                'disabled' => $options['data']->disablePublishChange(),
                'attr'     => $attr,
                'data'     => ('email' == $options['data']->getAlias()) ? true : $options['data']->getIsPublished(),
                'label'    => 'mautic.core.form.available',
            ]
        );

        $builder->add(
            'isRequired',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.core.required',
            ]
        );

        $builder->add(
            'isVisible',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.isvisible',
            ]
        );

        $builder->add(
            'isShortVisible',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.isshortvisible',
                'attr'  => [
                    'tooltip'         => 'mautic.lead.field.form.isshortvisible.tooltip',
                    'data-disable-on' => '{"leadfield_object":"company"}',
                ],
            ]
        );

        $builder->add(
            'isListable',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.islistable',
            ]
        );

        $constraints = [];

        if (false === $options['data']->isIsindex() && false === $this->indexHelper->isNewIndexAllowed()) {
            $constraints[] = new IsFalse(['message' => 'mautic.lead.field.form.index_count.error']);
        }

        $builder->add(
            'isIndex',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.lead.field.indexable',
                'label_attr' => ['class' => 'control-label'],
                'yes_label'  => 'mautic.lead.field.indexable.yes',
                'no_label'   => 'mautic.lead.field.indexable.no',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => $this->translator->trans('mautic.lead.field.form.isIndex.tooltip', ['%indexCount%' => $this->indexHelper->getIndexCount(), '%maxCount%' => $this->indexHelper->getMaxCount()]),
                    'readonly'=> (false === $isIndex && $this->indexHelper->getIndexCount() >= $this->indexHelper->getMaxCount()),
                ],
                'required'    => false,
                'constraints' => $constraints,
            ]
        );

        $data = $options['data']->isUniqueIdentifier();
        $builder->add(
            'isUniqueIdentifer',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.isuniqueidentifer',
                'attr'  => [
                    'tooltip'         => 'mautic.lead.field.form.isuniqueidentifer.tooltip',
                    'onchange'        => 'Mautic.displayUniqueIdentifierWarning(this);',
                    'data-disable-on' => '{"leadfield_object":"company"}',
                ],
                'data' => (!empty($data)),
            ]
        );

        $builder->add(
            'isPubliclyUpdatable',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.ispubliclyupdatable',
                'attr'  => [
                    'tooltip' => 'mautic.lead.field.form.ispubliclyupdatable.tooltip',
                ],
            ]
        );

        $builder->add(
            'object',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.lead.contact'    => 'lead',
                    'mautic.company.company' => 'company',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.lead.field.object',
                'placeholder' => false,
                'attr'        => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.updateLeadFieldOrderChoiceList();',
                ],
                'required'    => false,
                'disabled'    => ($disabled || !$new),
            ]
        );

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'        => LeadField::class,
                'validation_groups' => function (FormInterface $form): array {
                    $data = $form->getData();

                    $groups = ['Default'];

                    if (in_array($data->getType(), $this->indexableFieldsWithLimits)) {
                        $groups[] = 'indexableFieldWithLimits';
                    }

                    return $groups;
                },
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'leadfield';
    }

    public static function validateDefaultValue(?string $value, ExecutionContextInterface $context): void
    {
        if (empty($value)) {
            return;
        }

        /** @var LeadField $field */
        $field = $context->getRoot()->getViewData();

        if (in_array($field->getType(), self::$fieldsWithNoLengthLimit)) {
            return;
        }

        $limit              = $field->getCharLengthLimit();
        $defaultValueLength = mb_strlen($value);

        if ($defaultValueLength <= $limit) {
            return;
        }

        $translationParameters = [
            '%currentLength%'           => $defaultValueLength,
            '%defaultValueLengthLimit%' => $limit,
        ];

        $context
            ->buildViolation('mautic.lead.defaultValue.maxlengthexceeded', $translationParameters)
            ->addViolation();
    }

    private function addLengthValidationField(FormInterface $form, bool $new = true): void
    {
        $typesWithMaxLength = implode('","', $this->indexableFieldsWithLimits);

        $attr = [
            'class'        => 'form-control',
            'data-show-on' => '{
                "leadfield_type":["'.$typesWithMaxLength.'"]
             }',
        ];

        if (false === $new) {
            $attr['readonly'] = 'readonly';
        }

        $form->add(
            'charLengthLimit',
            NumberType::class,
            [
                'label'       => 'mautic.lead.field.form.maximum.character.length',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => $attr,
                'constraints' => [
                    new Assert\NotBlank(['groups' => 'indexableFieldWithLimits']),
                    new Assert\Range(['min' => 1, 'max' => 255, 'groups' => 'indexableFieldWithLimits']),
                ],
            ]
        );
    }
}
