<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\PointBundle\Form\Type\LeagueListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignEventPointType extends AbstractType
{
    use OperatorListTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'operator',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.campaign.event.point_operator',
                'multiple'          => false,
                'choices'           => $this->getOperatorsForFieldType([
                    'include' => [
                        '=',
                        '!=',
                        'gt',
                        'gte',
                        'lt',
                        'lte',
                    ],
                ]),
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
            ]
        );

        $builder->add(
            'score',
            NumberType::class,
            [
                'label'      => 'mautic.lead.campaign.event.point_score',
                'attr'       => ['class' => 'form-control'],
                'label_attr' => ['class' => 'control-label'],
                'scale'      => 0,
                'required'   => false,
            ]
        );

        $builder->add('league', LeagueListType::class, [
            'label'            => 'mautic.lead.campaign.event.point_league',
            'label_attr'       => ['class' => 'control-label'],
            'attr'             => ['class' => 'form-control'],
            'required'         => false,
            'by_reference'     => false,
            'return_entity'    => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'campaignevent_point';
    }
}
