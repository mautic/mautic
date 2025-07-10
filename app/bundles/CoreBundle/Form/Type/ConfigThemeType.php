<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class ConfigThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'brand_name',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.brand_name',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.brand_name.tooltip',
                ],
                'required' => false,
                'data'     => $options['data']['brand_name'] ?? '',
            ]
        );

        $builder->add(
            'primary_brand_color',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.primary_brand_color',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr'  => [
                    'class'        => 'form-control minicolors-input',
                    'tooltip'      => 'mautic.core.config.form.primary_brand_color.tooltip',
                    'data-toggle'  => 'color',
                    'autocomplete' => 'false',
                    'size'         => '7',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'theme',
            ThemeListType::class,
            [
                'label' => 'mautic.core.config.form.theme',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.theme.tooltip',
                ],
            ]
        );

        // Accent
        $builder->add(
            'accent',
            HiddenType::class,
            [
                'label'      => 'mautic.user.preferences.accent',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            $builder->create(
                'theme_import_allowed_extensions',
                TextType::class,
                [
                    'label'      => 'mautic.core.config.form.theme.import.allowed.extensions',
                    'label_attr' => [
                        'class' => 'control-label',
                    ],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required'   => false,
                ]
            )->addViewTransformer(new ArrayStringTransformer())
        );
    }

    public function getBlockPrefix(): string
    {
        return 'themeconfig';
    }
}
