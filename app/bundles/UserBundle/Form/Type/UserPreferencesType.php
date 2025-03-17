<?php

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class UserPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Theme
        $builder->add(
            'theme',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.theme',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        // Reduce Transparency
        $builder->add(
            'reduce_transparency',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.reduce_transparency',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
            ]
        );

        // Reduce Motion
        $builder->add(
            'reduce_motion',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.reduce_motion',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
            ]
        );

        // Contrast Borders
        $builder->add(
            'contrast_borders',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.contrast_borders',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
            ]
        );

        // Enable Underlines
        $builder->add(
            'enable_underlines',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.enable_underlines',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
            ]
        );
    }
}
