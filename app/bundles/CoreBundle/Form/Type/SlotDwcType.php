<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SlotDwcType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.dynamicContent.send.slot_name',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'tooltip'         => 'mautic.dynamicContent.send.slot_name.tooltip',
                    'class'           => 'form-control',
                    'data-slot-param' => 'slot-name',
                    'value'           => 'CHANGE_ME',
                ],
            ]
        );

        $builder->add(
            'content',
            TextareaType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control editor',
                    'data-slot-param' => 'content',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'slot_dwc';
    }
}
