<?php

namespace Mautic\PointBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Form\Type\LeadFieldsType;
use Mautic\PointBundle\Entity\PointInsight;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Model\FieldModel;

class PointInsightType extends AbstractType
{
    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('point.insight', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'point',
            ]
        );

        $builder->add(
            'isPublished',
            YesNoButtonGroupType::class
        );

        $insightTypes = [
            'mautic.point.insight.compare_point_groups' => 'compare_point_groups',
        ];

        $builder->add(
            'insightType',
            ChoiceType::class,
            [
                'choices'    => $insightTypes,
                'label'      => 'mautic.point.insight.type',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required'    => true,
                'placeholder' => 'mautic.core.form.chooseone',
                'data'        => 'compare_point_groups',
            ]
        );

        $builder->add(
            'pointGroups',
            GroupListType::class,
            [
                'label'      => 'mautic.point.insight.pointgroups.compare',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'                => 'form-control',
                    'data-toggle'          => 'multiselect',
                    'tooltip'              => 'mautic.point.insight.action.set_custom_field.tooltip',
                ],
                'multiple'    => true,
                'required'    => false,
                'return_entity' => false,
                'placeholder' => 'mautic.core.form.choosemultiple',
            ]
        );

        $insightActions = [
            'mautic.point.insight.action.set_custom_field' => 'set_custom_field',
        ];

        $builder->add(
            'insightAction',
            ChoiceType::class,
            [
                'choices'    => $insightActions,
                'label'      => 'mautic.point.insight.action',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required'    => true,
                'placeholder' => 'mautic.core.form.chooseone',
                'data'        => 'set_custom_field',
            ]
        );

        $builder->add(
            'customField',
            ChoiceType::class,
            [
                'label'      => 'mautic.point.insight.customfield',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'            => 'form-control',
                    'data-placeholder' => 'mautic.core.form.chooseone',
                ],
                'required'    => false,
                'placeholder' => 'mautic.core.form.chooseone',
                'choices'     => ArrayHelper::flipArray($this->fieldModel->getFieldList(
                    true,
                    true,
                    [
                        'isPublished' => true,
                        'object'      => 'lead',
                        'type'        => 'text'
                    ]
                )),
            ]
        );

        if (!empty($options['action'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PointInsight::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'pointinsight';
    }
} 