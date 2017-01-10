<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TwitterType.
 */
class TwitterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('count', 'choice', [
            'choices' => [
                'horizontal' => 'mautic.integration.Twitter.share.layout.horizontal',
                'vertical'   => 'mautic.integration.Twitter.share.layout.vertical',
                'none'       => 'mautic.integration.Twitter.share.layout.none',
            ],
            'label'       => 'mautic.integration.Twitter.share.layout',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
        ]);

        $builder->add('text', 'text', [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.text',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.text.pagetitle',
            ],
        ]);

        $builder->add('via', 'text', [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.via',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.username',
                'preaddon'    => 'fa fa-at',
            ],
        ]);

        $builder->add('related', 'text', [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.related',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.username',
                'preaddon'    => 'fa fa-at',
            ],
        ]);

        $builder->add('hashtags', 'text', [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.hashtag',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.hashtag.placeholder',
                'preaddon'    => 'symbol-hashtag',
            ],
        ]);

        $builder->add('size', 'yesno_button_group', [
            'no_value'  => 'medium',
            'yes_value' => 'large',
            'label'     => 'mautic.integration.Twitter.share.largesize',
            'data'      => (!empty($options['data']['size'])) ? $options['data']['size'] : 'medium',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'socialmedia_twitter';
    }
}
