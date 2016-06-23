<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            array(
                'label'      => 'mautic.social.monitoring.twitter.tweet.text',
                'required'   => true,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'tooltip' => 'mautic.social.monitoring.twitter.tweet.text.tooltip',
                    'class'   => 'form-control'
                )
            )
        );

        $builder->add(
            'asset_link',
            'asset_list',
            array(
                'label'       => 'mautic.social.monitoring.twitter.assets',
                'empty_value' => 'mautic.social.monitoring.list.choose',
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.social.monitoring.twitter.assets.descr'
                )
            )
        );

        $builder->add(
            'page_link',
            'page_list',
            array(
                'label'       => 'mautic.social.monitoring.twitter.pages',
                'empty_value' => 'mautic.social.monitoring.list.choose',
                'label_attr'  => array('class' => 'control-label'),
                'multiple'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.social.monitoring.twitter.pages.descr'
                )
            )
        );

        $builder->add(
            'handle',
            'button',
            array(
                'label' => '@',
                'attr'  => array(
                    'class' => 'form-control btn-primary',
                )
            )
        );
    }

    public function getName()
    {
        return "twitter_tweet";
    }
}