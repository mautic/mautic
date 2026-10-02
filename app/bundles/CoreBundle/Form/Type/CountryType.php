<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class CountryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices_as_values' => true,
            'choices'           => FormFieldHelper::getCountryChoices(),
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
            'multiple'          => false,
            'expanded'          => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
