<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'           => FormFieldHelper::getRegionChoices(),
                'choice_value'      => fn ($state) => $state,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'multiple'          => false,
                'expanded'          => false,
            ]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
