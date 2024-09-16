<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class YesNoButtonGroupType extends AbstractType
{
    public function getParent(): ?string
    {
        return ButtonGroupType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'yesno_button_group';
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
                'label'             => 'mautic.core.form.active',
                'placeholder'       => false,
                'required'          => false,
                'no_label'          => 'mautic.core.form.no',
                'no_value'          => 0,
                'yes_label'         => 'mautic.core.form.yes',
                'yes_value'         => 1,
            ]
        );
    }
}
