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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class TweetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'tweet_text',
            'textarea',
            [
                'label'      => 'mautic.social.monitoring.twitter.tweet.text',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'mautic.social.monitoring.twitter.tweet.text.tooltip',
                    'class'   => 'form-control tweet-message',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'asset_link',
            'asset_list',
            [
                'label'       => 'mautic.social.monitoring.twitter.assets',
                'empty_value' => 'mautic.social.monitoring.list.choose',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'attr'        => [
                    'class'   => 'form-control tweet-insert-asset',
                    'tooltip' => 'mautic.social.monitoring.twitter.assets.descr',
                ],
            ]
        );

        $builder->add(
            'page_link',
            'page_list',
            [
                'label'       => 'mautic.social.monitoring.twitter.pages',
                'empty_value' => 'mautic.social.monitoring.list.choose',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'attr'        => [
                    'class'   => 'form-control tweet-insert-page',
                    'tooltip' => 'mautic.social.monitoring.twitter.pages.descr',
                ],
            ]
        );

        $builder->add(
            'handle',
            'button',
            [
                'label' => '@',
                'attr'  => [
                    'class' => 'form-control btn-primary tweet-insert-handle',
                ],
            ]
        );
    }

    public function getName()
    {
        return 'twitter_tweet';
    }
}
