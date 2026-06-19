<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\PointBundle\Entity\PointInsight;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PointInsightType extends AbstractType
{
    public function __construct(
        private FieldList $fieldList,
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
            'infoText',
            TextType::class,
            [
                'label'      => 'mautic.point.insight.action.set_custom_field_to_winning_point_group',
                'label_attr' => ['class' => 'control-label'],
                'mapped'     => false,
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.point.insight.action.set_custom_field_to_winning_point_group.tooltip',
                    'style'   => 'display: none;',
                ],
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
            'mautic.point.insight.compare_point_groups' => PointInsight::INSIGHT_TYPE_COMPARE_POINT_GROUPS,
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
            ]
        );

        $insightActions = [
            'mautic.point.insight.action.set_custom_field' => PointInsight::INSIGHT_ACTION_SET_CUSTOM_FIELD,
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
                ],
                'multiple'      => true,
                'required'      => false,
                'return_entity' => false,
                'placeholder'   => 'mautic.core.form.choosemultiple',
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
                'choices'     => ArrayHelper::flipArray($this->fieldList->getFieldList(
                    true,
                    true,
                    [
                        'isPublished' => true,
                        'object'      => 'lead',
                        'type'        => 'text',
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
