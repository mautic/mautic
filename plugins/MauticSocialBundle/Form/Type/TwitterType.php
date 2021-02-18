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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TwitterType.
 */
class TwitterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('count', ChoiceType::class, [
            'choices' => [
                'mautic.integration.Twitter.share.layout.horizontal' => 'horizontal',
                'mautic.integration.Twitter.share.layout.vertical'   => 'vertical',
                'mautic.integration.Twitter.share.layout.none'       => 'none',
            ],
            'label'             => 'mautic.integration.Twitter.share.layout',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
        ]);

        $builder->add('text', TextType::class, [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.text',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.text.pagetitle',
            ],
        ]);

        $builder->add('via', TextType::class, [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.via',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.username',
                'preaddon'    => 'fa fa-at',
            ],
        ]);

        $builder->add('related', TextType::class, [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.related',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.username',
                'preaddon'    => 'fa fa-at',
            ],
        ]);

        $builder->add('hashtags', TextType::class, [
            'label_attr' => ['class' => 'control-label'],
            'label'      => 'mautic.integration.Twitter.share.hashtag',
            'required'   => false,
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautic.integration.Twitter.share.hashtag.placeholder',
                'preaddon'    => 'symbol-hashtag',
            ],
        ]);

        $builder->add('size', YesNoButtonGroupType::class, [
            'no_value'  => 'medium',
            'yes_value' => 'large',
            'label'     => 'mautic.integration.Twitter.share.largesize',
            'data'      => (!empty($options['data']['size'])) ? $options['data']['size'] : 'medium',
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'socialmedia_twitter';
    }
}
