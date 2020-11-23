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
class SlotVideoType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('url', 'text', [
            'label'      => 'mautic.core.video.url',
            'label_attr' => ['class' => 'control-label'],
            'required'   => true,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'video-url',
            ],
        ])->add('alt', 'text', [
            'label'      => 'mautic.core.title',
            'label_attr' => ['class' => 'control-label'],
            'required'   => true,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'video-alt',
            ],
        ])->add('color', 'choice', [
            'label'       => 'mautic.core.color',
            'label_attr'  => ['class' => 'control-label'],
            'required'    => true,
            'attr'        => [
                'class'           => 'form-control',
                'data-slot-param' => 'video-color',
            ],
            'choice_list' => [
                'mautic.core.video.color.light' => 'slot-video-light',
                'mautic.core.video.color.dark'  => 'slot-video-dark',
            ],
        ]);

        parent::buildForm($builder, $options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_video';
    }
}
