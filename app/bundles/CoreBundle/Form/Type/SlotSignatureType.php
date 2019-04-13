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

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotImageType.
 */
class SlotSignatureType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('image', 'url', [
            'label'      => 'mautic.core.signature.image',
            'label_attr' => ['class' => 'control-label'],
            'required'   => true,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-image',
                'postaddon_text'  => '<i class="fa fa-image"></i>',
            ],
        ])->add('image-border-radius', 'number', [
                'label'      => 'mautic.core.button.border.radius',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'signature-image-border-radius',
                    'postaddon_text'  => '<strong>PX</strong>',
                ],
            ]
        )->add('name', 'text', [
            'label'      => 'mautic.core.signature.name',
            'label_attr' => ['class' => 'control-label'],
            'required'   => true,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-name',
                'postaddon_text'  => '<i class="fa fa-user"></i>',
            ],
        ])->add('bio', 'text', [
            'label'      => 'mautic.core.signature.bio',
            'label_attr' => ['class' => 'control-label'],
            'required'   => true,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-bio',
                'postaddon_text'  => '<i class="fa fa-paragraph"></i>',
            ],
        ])->add('mobile', 'text', [
            'label'      => 'mautic.core.signature.mobile',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-mobile',
                'postaddon_text'  => '<i class="fa fa-phone"></i>',
            ],
        ])->add('site', 'url', [
            'label'      => 'mautic.core.signature.site',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-site',
                'postaddon_text'  => '<i class="fa fa-globe"></i>',
            ],
        ])->add('email', 'email', [
            'label'      => 'mautic.core.signature.email',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-email',
                'postaddon_text'  => '<i class="fa fa-envelope"></i>',
            ],
        ])->add('contrast', 'text', [
            'label'      => 'mautic.core.signature.contrast',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-contrast',
                'data-toggle'     => 'color',
            ],
        ])->add('neutral', 'text', [
            'label'      => 'mautic.core.signature.neutral',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-neutral',
                'data-toggle'     => 'color',
            ],
        ])->add('link', 'text', [
            'label'      => 'mautic.core.signature.link',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'signature-link',
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
        return 'slot_signature';
    }
}
