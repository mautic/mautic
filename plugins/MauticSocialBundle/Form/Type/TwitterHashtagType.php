<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TwitterHashtagType extends TwitterAbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('hashtag', TextType::class, [
            'label'      => 'mautic.social.monitoring.twitter.hashtag',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'tooltip'  => 'mautic.social.monitoring.twitter.hashtag.tooltip',
                'class'    => 'form-control',
                'preaddon' => 'symbol-hashtag',
            ],
        ]);

        $builder->add('checknames', ChoiceType::class, [
            'choices' => [
                'mautic.social.monitoring.twitter.no'  => '0',
                'mautic.social.monitoring.twitter.yes' => '1',
            ],
            'label'             => 'mautic.social.monitoring.twitter.namematching',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.social.monitoring.twitter.namematching.tooltip',
            ],
        ]);

        // pull in the parent type's form builder
        parent::buildForm($builder, $options);
    }

    public function getBlockPrefix()
    {
        return 'twitter_hashtag';
    }
}
