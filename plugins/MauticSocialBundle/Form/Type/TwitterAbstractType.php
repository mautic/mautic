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
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

class TwitterAbstractType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Disabled due to Twitter restrictions
        /*
        $builder->add('tweet_action', 'choice', array(
            'empty_value' => 'mautic.social.monitoring.list.choose',
            'choice_list' => new ChoiceList(
                array('retweet','favorite'),
                array(
                    'mautic.social.monitoring.list.action.retweet',
                    'mautic.social.monitoring.list.action.favorite')
            ),
            'label'      => 'mautic.social.monitoring.twitter.interact.label',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'tooltip' => 'mautic.social.monitoring.twitter.interact.tooltip',
                'class' => 'form-control',

            ),
            'required' => false
        ));
        */
    }

    public function getName()
    {
        return 'twitter_abstract';
    }
}
