<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SlotSeparatorType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'color',
            TextType::class,
            [
                'label'      => 'mautic.core.separator.color',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-toggle'     => 'color',
                    'data-slot-param' => 'separator-color',
                ],
            ]
        );

        $builder->add(
            'thickness',
            NumberType::class,
            [
                'label'      => 'mautic.core.separator.thickness',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'separator-thickness',
                ],
            ]
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'slot_separator';
    }
}
