<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotImageType.
 */
class SlotImageType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'align',
            ButtonGroupType::class,
            [
                'label'             => 'mautic.core.image.position',
                'label_attr'        => ['class' => 'control-label'],
                'required'          => false,
                'attr'              => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'align',
                ],
                'choices'           => [
                    'mautic.core.left'   => 0,
                    'mautic.core.center' => 1,
                    'mautic.core.right'  => 2,
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
        return 'slot_image';
    }
}
