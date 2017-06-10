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

use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotImageCardType.
 */
class SlotImageCardType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'caption',
            TextType::class,
            [
                'label'      => 'mautic.core.image.caption',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'cardcaption',
                ],
            ]
        );

        $builder->add('align', 'button_group', [
            'label'      => 'mautic.core.image.position',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'align',
            ],
            'choice_list' => new ChoiceList(
                ['left', 'center', 'right'],
                ['mautic.core.left', 'mautic.core.center', 'mautic.core.right']
            ),
        ]);

        $builder->add('text-align', 'button_group', [
            'label'      => 'mautic.core.caption.position',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'text-align',
            ],
            'choice_list' => new ChoiceList(
                ['left', 'center', 'right'],
                ['mautic.core.left', 'mautic.core.center', 'mautic.core.right']
            ),
        ]);

        $builder->add('background-color', 'text', [
            'label'      => 'mautic.core.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color',
            ],
        ]);

        $builder->add('caption-color', 'text', [
            'label'      => 'mautic.core.caption.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'caption-color',
                'data-toggle'     => 'color',
            ],
        ]);

        $builder->add('color', 'text', [
            'label'      => 'mautic.core.text.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'color',
                'data-toggle'     => 'color',
            ],
        ]);

        parent::buildForm($builder, $options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_imagecard';
    }
}
