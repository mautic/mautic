<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Custom IntegerType that supports constraints.
 */
final class ConstrainedIntegerType extends IntegerType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined('constraints');
        $resolver->setAllowedTypes('constraints', ['array']);
        $resolver->setAllowedValues('constraints', function ($constraints): bool {
            if (!is_array($constraints)) {
                return false;
            }

            return array_all($constraints, fn ($constraint): bool => $constraint instanceof Constraint);
        });
    }
}
