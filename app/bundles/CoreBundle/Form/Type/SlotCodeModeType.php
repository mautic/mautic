<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class SlotCodeModeType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'content',
            TextareaType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'content',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'slot_codemode';
    }
}
