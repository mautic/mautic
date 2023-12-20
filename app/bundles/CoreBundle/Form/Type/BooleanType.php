<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => [],
            'multiple'          => false,
            'expanded'          => true,
            'choices_as_values' => true,
        ]);
    }

    public function getParent()
    {
        return YesNoButtonGroupType::class;
    }
}
