<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class DateSpanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $filterData = $options['data'];
        $interval   = $filterData['interval'] ?? '';

        $builder->add(
            'interval',
            IntegerType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'step'  => 1,
                    'min'   => 1,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
                'data'  => (int) $interval,
            ]
        );

        $builder->add(
            'unit',
            ChoiceType::class,
            [
                'label'      => 'mautic.core.date.to',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'choices'    => [
                    'mautic.core.time.days'   => 'day',
                    'mautic.core.time.months' => 'month',
                    'mautic.core.time.years'  => 'year',
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'datespan';
    }
}
