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
        $resolver->setAllowedValues('constraints', function ($constraints) {
            if (!is_array($constraints)) {
                return false;
            }

            foreach ($constraints as $constraint) {
                if (!$constraint instanceof Constraint) {
                    return false;
                }
            }

            return true;
        });
    }
}
