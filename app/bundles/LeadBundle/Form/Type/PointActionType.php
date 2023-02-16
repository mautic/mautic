<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\PointBundle\Form\Type\LeagueListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class PointActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'points',
            NumberType::class,
            [
                'label'       => 'mautic.lead.lead.event.points',
                'attr'        => ['class' => 'form-control'],
                'label_attr'  => ['class' => 'control-label'],
                'scale'       => 0,
                'data'        => (isset($options['data']['points'])) ? $options['data']['points'] : 0,
                'constraints' => [
                    new NotEqualTo(
                        [
                            'value'   => '0',
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add('league', LeagueListType::class, [
            'label'            => 'mautic.lead.campaign.event.point_league',
            'label_attr'       => ['class' => 'control-label'],
            'attr'             => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.lead.campaign.event.point_league.help',
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
        return 'leadpoints_action';
    }
}
