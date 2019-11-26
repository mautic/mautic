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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlideshowGlobalConfigType.
 */
class SlideshowGlobalConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('slideshow_enabled', YesNoButtonGroupType::class, [
            'label' => 'mautic.page.slideshow.enabled',
            'data'  => (bool) isset($options['data']['slideshow_enabled']) ? $options['data']['slideshow_enabled'] : true,
            'attr'  => [
                'tooltip'          => 'mautic.page.slideshow.enabled.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
        ]);

        $builder->add('dot_navigation', YesNoButtonGroupType::class, [
            'label' => 'mautic.page.slideshow.dot.navigation',
            'data'  => (bool) isset($options['data']['dot_navigation']) ? $options['data']['dot_navigation'] : true,
            'attr'  => [
                'tooltip'          => 'mautic.page.slideshow.dot.navigation.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
        ]);

        $builder->add('arrow_navigation', YesNoButtonGroupType::class, [
            'label' => 'mautic.page.slideshow.arrow.navigation',
            'data'  => (bool) isset($options['data']['arrow_navigation']) ? $options['data']['arrow_navigation'] : true,
            'attr'  => [
                'tooltip'          => 'mautic.page.slideshow.arrow.navigation.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
        ]);

        $builder->add('height', TextType::class, [
            'label'      => 'mautic.page.slideshow.height',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'tooltip'          => 'mautic.page.slideshow.height.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['height']) ? $options['data']['height'] : '',
        ]);

        $builder->add('width', TextType::class, [
            'label'      => 'mautic.page.slideshow.width',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'tooltip'          => 'mautic.page.slideshow.width.desc',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['width']) ? $options['data']['width'] : '',
        ]);

        $builder->add('background_color', TextType::class, [
            'label'      => 'mautic.page.slideshow.background.color',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'tooltip'          => 'mautic.page.slideshow.background.color.desc',
                'data-toggle'      => 'color',
                'data-slot-config' => $options['data']['slot'],
            ],
            'required' => false,
            'data'     => isset($options['data']['background_color']) ? $options['data']['background_color'] : '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'slideshow_config';
    }
}
