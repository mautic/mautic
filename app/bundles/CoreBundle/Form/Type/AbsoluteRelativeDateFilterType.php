<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AbsoluteRelativeDateFilterType extends AbstractType
{
    public const ABSOLUTE_DATE_TYPE = 'absolute';
    public const RELATIVE_DATE_TYPE = 'relative';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateTypeMode             = self::ABSOLUTE_DATE_TYPE;
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
            'mautic.core.date.type.absolute' => self::ABSOLUTE_DATE_TYPE,
            'mautic.core.date.type.relative' => self::RELATIVE_DATE_TYPE,
        ];

        $builder->add(
            'dateTypeMode',
            ButtonGroupType::class,
            [
                'choices'           => $choices,
                'expanded'          => true,
                'multiple'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'label'             => false,
                'placeholder'       => false,
                'required'          => false,
                'attr'              => ['onchange' => 'Mautic.absoluteRelativeDateFilterToggleType(this);'],
                'data'              => $dateTypeMode,
            ]
        );

        $attr = ['class' => 'form-control'];
        if (isset($options['attr'])) {
            $attr = array_merge($attr, $options['attr']);
        }

        $builder->add(
            'absoluteDate',
            TextType::class,
            [
                'label'    => false,
                'attr'     => $attr,
                'data'     => $absoluteDate,
            ]
        );

        $builder->add(
            'relativeDateInterval',
            IntegerType::class,
            [
                'label' => false,
                'attr'  => [
                    'class'    => 'form-control',
                    'preaddon' => 'symbol-hashtag',
                    'step'     => 1,
                    'min'      => 1,
                ],
                'constraints' => [
                    new Assert\Positive(),
                ],
                'data'  => (int) $relativeDateInterval,
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

    public function getBlockPrefix(): string
    {
        return 'absolute_relative_date_filter';
    }
}
