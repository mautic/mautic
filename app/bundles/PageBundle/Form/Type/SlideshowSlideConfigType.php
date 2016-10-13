<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlideshowSlideConfigType.
 */
class SlideshowSlideConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('slides:'.$options['data']['key'].':remove', 'checkbox', [
            'label' => 'mautic.page.slideshow.remove',
            'attr'  => [
                'data-slot-config'  => $options['data']['slot'],
                'data-remove-slide' => $options['data']['key'],
            ],
            'required' => false,
        ]);

        $builder->add('slides:'.$options['data']['key'].':captionheader', 'text', [
            'label'      => 'mautic.page.slideshow.caption.header',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'tooltip'          => 'mautic.page.slideshow.caption.header.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['captionheader']) ? $options['data']['captionheader'] : '',
        ]);

        $builder->add('slides:'.$options['data']['key'].':captionbody', 'text', [
            'label'      => 'mautic.page.slideshow.caption.body',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'tooltip'          => 'mautic.page.slideshow.caption.body.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['captionbody']) ? $options['data']['captionbody'] : '',
        ]);

        $builder->add('slides:'.$options['data']['key'].':background-image', 'text', [
            'label'      => 'mautic.page.slideshow.background',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control background-image',
                'tooltip'          => 'mautic.page.slideshow.background.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['background-image']) ? $options['data']['background-image'] : '',
        ]);

        $builder->add('slides:'.$options['data']['key'].':order', 'hidden', [
            'attr' => [
                'class'            => 'slide-order',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['order']) ? $options['data']['order'] : $options['data']['key'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'slideshow_slide_config';
    }
}
