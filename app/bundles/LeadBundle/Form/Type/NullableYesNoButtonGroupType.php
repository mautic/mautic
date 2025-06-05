<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NullableYesNoButtonGroupType extends AbstractType
{
    public function getParent(): string
    {
        return ButtonGroupType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'           => fn (Options $options): array => [
                    $options['no_label']  => $options['no_value'],
                    $options['yes_label'] => $options['yes_value'],
                ],
                'choice_value'      => function ($choiceKey) {
                    if (null === $choiceKey || '' === $choiceKey) {
                        return null;
                    }

                    return (is_string($choiceKey) && !is_numeric($choiceKey)) ? $choiceKey : (int) $choiceKey;
                },
                'expanded'          => true,
                'multiple'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class'           => 'form-control',
                ],
                'label'             => 'mautic.core.form.active',
                'placeholder'       => true,
                'required'          => false,
                'no_label'          => 'mautic.core.form.no',
                'no_value'          => 0,
                'yes_label'         => 'mautic.core.form.yes',
                'yes_value'         => 1,
                'empty_data'        => null,
                'data'              => null,
            ]
        );
    }
}
