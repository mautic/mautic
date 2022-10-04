<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class BuilderSectionType.
 */
class BuilderSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Content - Background Color
        $builder->add(
            'content-background-color',
            TextType::class,
            [
                'label'      => 'mautic.core.content.background.color',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'background-color',
                    'data-toggle'     => 'color',
                ],
            ]
        );

        // Content - Background Image
        $builder->add(
            'content-background-image',
            UrlType::class,
            [
                'label'      => 'mautic.core.content.background.image',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                    'value' => 'none',
                ],
            ]
        );

        // Content - Background Repeat
        $builder->add(
            'content-background-repeat',
            ChoiceType::class,
            [
                'choices' => [
                    'no-repeat'    => 'no-repeat',
                    'repeat'       => 'repeat',
                    'repeat-x'     => 'repeat-x',
                    'repeat-y'     => 'repeat-y',
                    'space'        => 'space',
                    'round'        => 'round',
                    'repeat-space' => 'repeat-space',
                    'space-round'  => 'space-round',
                ],
                'label'      => 'mautic.core.content.background.repeat',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => ['class' => 'form-control'],
            ]
        );

        // Content - Background Size Width
        $builder->add(
            'content-background-size',
            TextType::class,
            [
                'label'      => 'mautic.core.content.background.size',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => ['class' => 'form-control'],
            ]
        );

        // Content - Background Position
        $builder->add(
            'content-background-position',
            TextType::class,
            [
                'label'      => 'mautic.core.content.background.position',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => ['class' => 'form-control'],
            ]
        );

        // Wrapper - Background Color
        $builder->add(
            'wrapper-background-color',
            TextType::class,
            [
                'label'      => 'mautic.core.wrapper.background.color',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'background-color',
                    'data-toggle'     => 'color',
                ],
            ]
        );

        // Wrapper - Background Image
        $builder->add(
            'wrapper-background-image',
            UrlType::class,
            [
                'label'      => 'mautic.core.wrapper.background.image',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                    'value' => 'none',
                ],
            ]
        );

        // Wrapper - Background Repeat
        $builder->add(
            'wrapper-background-repeat',
            ChoiceType::class,
            [
                'choices'    => [
                    'no-repeat'    => 'no-repeat',
                    'repeat'       => 'repeat',
                    'repeat-x'     => 'repeat-x',
                    'repeat-y'     => 'repeat-y',
                    'space'        => 'space',
                    'round'        => 'round',
                    'repeat-space' => 'repeat-space',
                    'space-round'  => 'space-round',
                ],
                'label'             => 'mautic.core.wrapper.background.repeat',
                'label_attr'        => ['class' => 'control-label'],
                'required'          => false,
                'attr'              => ['class' => 'form-control'],
            ]
        );

        // Wrapper - Background Size Width
        $builder->add(
            'wrapper-background-size',
            TextType::class,
            [
                'label'      => 'mautic.core.wrapper.background.size',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => ['class' => 'form-control'],
            ]
        );

        // Wrapper - Background Position
        $builder->add(
            'wrapper-background-position',
            TextType::class,
            [
                'label'      => 'mautic.core.wrapper.background.position',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'builder_section';
    }
}
