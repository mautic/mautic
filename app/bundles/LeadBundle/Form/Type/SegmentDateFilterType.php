<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SegmentDateFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateTypeMode             = 'absolute';
        $absoluteDate             = '';
        $relativeDateInterval     = '1';
        $relativeDateIntervalUnit = 'd';

        $filterData = $options['data'];
        if (!empty($filterData)) {
            if (is_array($filterData)) {
                $absoluteDate             = $filterData['absoluteDate'];
                $dateTypeMode             = $filterData['dateTypeMode'] ?? $dateTypeMode;
                $relativeDateInterval     = $filterData['relativeDateInterval'] ?? $relativeDateInterval;
                $relativeDateIntervalUnit = $filterData['relativeDateIntervalUnit'] ?? $relativeDateIntervalUnit;
            } else {
                $absoluteDate = $filterData;
            }
        }

        $choices = [
            'absolute'  => 'Absolute',
            'relative'  => 'Relative',
        ];

        $builder->add(
            'dateTypeMode',
            ButtonGroupType::class,
            [
                'choices'           => array_flip($choices),
                'expanded'          => true,
                'multiple'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'label'             => false,
                'placeholder'       => false,
                'required'          => false,
                'attr'              => ['onchange' => 'Mautic.segmentDateFilterToggleType(this);'],
                'data'              => $dateTypeMode,
            ]
        );

        $builder->add(
            'absoluteDate',
            TextType::class,
            [
                'label'    => false,
                'attr'     => ['class' => 'form-control'],
                'data'     => $absoluteDate,
            ]
        );

        $builder->add(
            'relativeDateInterval',
            NumberType::class,
            [
                'label' => false,
                'attr'  => [
                    'class'    => 'form-control',
                    'preaddon' => 'symbol-hashtag',
                ],
                'data'  => $relativeDateInterval,
            ]
        );

        $builder->add(
            'relativeDateIntervalUnit',
            ChoiceType::class,
            [
                'choices'     => [
                    'mautic.lead.list.date.filter.intervalunit.choice.day'   => 'day',
                    'mautic.lead.list.date.filter.intervalunit.choice.month' => 'month',
                    'mautic.lead.list.date.filter.intervalunit.choice.year'  => 'year',
                ],
                'multiple'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'label'             => false,
                'attr'              => ['class' => 'form-control'],
                'placeholder'       => false,
                'required'          => false,
                'data'              => $relativeDateIntervalUnit,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'segment_date_filter';
    }
}
