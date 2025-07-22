<?php

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ColorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'primary',
            TextType::class,
            [
                'label'      => 'mautic.focus.form.primary_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'text',
            TextType::class,
            [
                'label'      => 'mautic.focus.form.text_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'button',
            TextType::class,
            [
                'label'      => 'mautic.focus.form.button_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'button_text',
            TextType::class,
            [
                'label'      => 'mautic.focus.form.button_text_color',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'color',
                    'onchange'    => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'focus_color';
    }
}
