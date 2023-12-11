<?php

namespace Mautic\LeadBundle\Form\Type;

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
use Mautic\LeadBundle\Field\IdentifierFields;
use Mautic\LeadBundle\Form\DataTransformer\FieldToOrderTransformer;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FieldType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $em,
        private Translator $translator,
        private IdentifierFields $identifierFields
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
                'attr'       => ['class' => 'form-control', 'length' => 50],
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
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.field.form.group.help',
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

        $builder->add(
            'properties_textarea_template',
            YesNoButtonGroupType::class,
            [
                'label'       => 'mautic.lead.field.form.properties.allowhtml',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'mapped'      => false,
                'data'        => $options['data']->getProperties()['allowHtml'] ?? false,
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
                'data'        => '',
                'placeholder' => ' x ',
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
                'required'   => false,
                'disabled'   => $disableDefaultValue,
            ]
        );

        $formModifier = function (FormEvent $event) use ($listChoices, $type, $options, $disableDefaultValue): array {
            $cleaningRules = [];
            $form          = $event->getForm();
            $data          = $event->getData();
            $type          = (is_array($data)) ? ($data['type'] ?? $type) : $data->getType();

            switch ($type) {
                case 'multiselect':
                case 'select':
                case 'lookup':
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
                            'label'      => 'mautic.core.defaultvalue',
                            'label_attr' => ['class' => 'control-label is-chosen'],
                            'attr'       => ['class' => 'form-control'],
                            'required'   => false,
                            'choices'    => array_flip($list),
                            'multiple'   => 'multiselect' === $type,
                            'data'       => 'multiselect' === $type && is_string($options['data']->getDefaultValue()) ? explode('|', $options['data']->getDefaultValue()) : $options['data']->getDefaultValue(),
                            'disabled'   => $disableDefaultValue,
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
                case 'number':
                case 'tel':
                case 'url':
                case 'email':
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
                            'required'   => false,
                            'disabled'   => $disableDefaultValue,
                        ]
                    );

                    break;
            }

            return $cleaningRules;
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier): void {
                $formModifier($event);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier, $disableDefaultValue): void {
                $data          = $event->getData();
                $cleaningRules = $formModifier($event);
                $masks         = !empty($cleaningRules) ? $cleaningRules : 'clean';
                // clean the data
                $data = InputHelper::_($data, $masks);

                if ((isset($data['group']) && 'social' === $data['group']) || !empty($data['isUniqueIdentifer']) || $disableDefaultValue) {
                    // Don't allow a default for social or unique identifiers
                    $data['defaultValue'] = null;
                }

                $event->setData($data);
            }
        );

        /** @var LeadFieldRepository $leadFieldRepository */
        $leadFieldRepository = $this->em->getRepository(LeadField::class);

        // get order list
        $transformer = new FieldToOrderTransformer($leadFieldRepository);
        $builder->add(
            $builder->create(
                'order',
                EntityType::class,
                [
                    'label'         => 'mautic.core.order',
                    'class'         => \Mautic\LeadBundle\Entity\LeadField::class,
                    'choice_label'  => 'label',
                    'label_attr'    => ['class' => 'control-label'],
                    'attr'          => ['class' => 'form-control'],
                    'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('f')->orderBy('f.order', \Doctrine\Common\Collections\Criteria::ASC),
                    'required'      => false,
                ]
            )->addModelTransformer($transformer)
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
                    'tooltip' => 'mautic.lead.field.form.isshortvisible.tooltip',
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

        $data = $options['data']->isUniqueIdentifier();
        $builder->add(
            'isUniqueIdentifer',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.field.form.isuniqueidentifer',
                'attr'  => [
                    'tooltip'  => 'mautic.lead.field.form.isuniqueidentifer.tooltip',
                    'onchange' => 'Mautic.displayUniqueIdentifierWarning(this)',
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
                'attr'        => ['class' => 'form-control'],
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
                'data_class' => LeadField::class,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadfield';
    }
}
