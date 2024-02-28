<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class MultiselectType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
            'multiple'          => true,
            'choices_as_values' => true,
            'expanded'          => false,
            'constraints'       => new Length(['max' => 191]),
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
