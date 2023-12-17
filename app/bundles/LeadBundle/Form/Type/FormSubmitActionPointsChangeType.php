<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\PointBundle\Form\Type\GroupListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class FormSubmitActionPointsChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'operator',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.lead.submitaction.operator',
                'attr'              => ['class' => 'form-control'],
                'label_attr'        => ['class' => 'control-label'],
                'choices'           => [
                    'mautic.lead.lead.submitaction.operator_plus'   => 'plus',
                    'mautic.lead.lead.submitaction.operator_minus'  => 'minus',
                    'mautic.lead.lead.submitaction.operator_times'  => 'times',
                    'mautic.lead.lead.submitaction.operator_divide' => 'divide',
                ],
            ]
        );

        $default = (empty($options['data']['points'])) ? 0 : (int) $options['data']['points'];
        $builder->add(
            'points',
            NumberType::class,
            [
                'label'      => 'mautic.lead.lead.submitaction.points',
                'attr'       => ['class' => 'form-control'],
                'label_attr' => ['class' => 'control-label'],
                'scale'      => 0,
                'data'       => $default,
            ]
        );

        $builder->add('group', GroupListType::class, [
            'label'            => 'mautic.lead.campaign.event.point_group',
            'label_attr'       => ['class' => 'control-label'],
            'attr'             => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.lead.campaign.event.point_group.help',
            ],
            'required'         => false,
            'by_reference'     => false,
            'return_entity'    => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_submitaction_pointschange';
    }
}
