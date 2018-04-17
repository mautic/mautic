<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class BuilderSectionType.
 */
class BuilderSectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Content - Background Color
        $builder->add('content-background-color', 'text', [
            'label'      => 'mautic.core.content.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color',
            ],
        ]);

        // Wrapper - background-color
        $builder->add('wrapper-background-color', 'text', [
            'label'      => 'mautic.core.wrapper.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color',
            ],
        ]);

        // Content - Background Image
        $builder->add('wrapper-background-image', 'url', [
            'label'      => 'mautic.core.wrapper.background.image',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'value'           => 'none',
                'data-slot-param' => 'background-image',
            ],
        ]);

        // Content - Background Repeat
        $builder->add(
            'wrapper-background-repeat',
            ChoiceType::class,
        [
            'choices' => [
                'no-repeat'     => 'no-repeat',
                'repeat'        => 'repeat',
                'repeat-x'      => 'repeat-x',
                'repeat-y'      => 'repeat-y',
                'space'         => 'space',
                'round'         => 'round',
                'repeat-space'  => 'repeat-space',
                'space-round'   => 'space-round',
            ],
            'label'      => 'mautic.core.wrapper.background.repeat',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-repeat',
                'data-toggle'     => 'background-repeat',
            ],
        ]
        );

        // Content - Background Size Width
        $builder->add('wrapper-background-size-width', 'text', [
            'label'      => 'mautic.core.wrapper.background.size.width',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-size',
                'data-toggle'     => 'background-size',
            ],
        ]);

        // Content - Background Size Height
        $builder->add('wrapper-background-size-height', 'text', [
            'label'      => 'mautic.core.wrapper.background.size.height',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-size',
                'data-toggle'     => 'background-size',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'builder_section';
    }
}
