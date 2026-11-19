<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class StandAloneButtonType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'standalone_button';
    }
}
