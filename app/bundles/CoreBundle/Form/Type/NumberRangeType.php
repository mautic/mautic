<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class NumberRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraints = [
            new Type(['type' => 'numeric', 'message' => 'This value should be a valid number.']),
            new NotBlank(['message' => 'mautic.core.value.required']),
        ];

        $builder->add(
            'number_from',
            TextType::class,
            [
                'label'       => false,
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'constraints' => $constraints,
            ]
        );

        $builder->add(
            'number_to',
            TextType::class,
            [
                'label'       => 'and',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => false,
                'constraints' => $constraints,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'numberrange';
    }
}
