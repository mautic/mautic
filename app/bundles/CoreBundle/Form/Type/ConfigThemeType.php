<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'theme',
            ThemeListType::class,
            [
                'label' => 'mautic.core.config.form.theme',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.template.help',
                ],
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'themeconfig';
    }
}
