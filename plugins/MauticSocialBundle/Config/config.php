<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Social Media',
    'description' => 'Enables integrations with Mautic supported social media services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes'      => array(
        'main' => array(
            'mautic_social_index'         => array(
                'path'       => '/monitoring/{page}',
                'controller' => 'MauticSocialBundle:Monitoring:index'
            ),
            'mautic_social_action'         => array(
                'path'       => '/monitoring/{objectAction}/{objectId}',
                'controller' => 'MauticSocialBundle:Monitoring:execute'
            ),
            'mautic_social_leads'          => array(
                'path'       => '/monitoring/view/{objectId}/leads/{page}',
                'controller' => 'MauticSocialBundle:Monitoring:leads',
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.social.formbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticSocialBundle\EventListener\FormSubscriber'
            ),
            'mautic.social.campaignbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticSocialBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.social.configbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticSocialBundle\EventListener\ConfigSubscriber'
            ),
        ),
        'forms' => array(
            'mautic.form.type.social.sociallogin'        => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\SocialLoginType',
                'arguments' => 'mautic.factory',
                'alias' => 'sociallogin'
            ),
            'mautic.form.type.social.facebook'        => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\FacebookType',
                'alias' => 'socialmedia_facebook'
            ),
            'mautic.form.type.social.twitter'         => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterType',
                'alias' => 'socialmedia_twitter'
            ),
            'mautic.form.type.social.googleplus'      => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\GooglePlusType',
                'alias' => 'socialmedia_googleplus'
            ),
            'mautic.form.type.social.linkedin'        => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\LinkedInType',
                'alias' => 'socialmedia_linkedin'
            ),
            'mautic.social.form.type.twitter.tweet' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetType',
                'arguments' => 'mautic.factory',
                'alias'     => 'twitter_tweet',
            ),
            'mautic.social.form.type.monitoring' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\MonitoringType',
                'alias'     => 'monitoring'
            ),
            'mautic.social.form.type.network.twitter.abstract' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterAbstractType',
                'alias'     => 'twitter_abstract'
            ),
            'mautic.social.form.type.network.twitter.hashtag' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterHashtagType',
                'alias'     => 'twitter_hashtag'
            ),
            'mautic.social.form.type.network.twitter.mention' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterMentionType',
                'alias'     => 'twitter_handle'
            ),
            'mautic.social.form.type.network.twitter.custom' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterCustomType',
                'alias'     => 'twitter_custom'
            ),
            'mautic.social.config' => array(
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\ConfigType',
                'alias'     => 'social_config',
                'arguments' => 'mautic.factory',
            )
        ),
    ),
    'menu'     => array(
        'main' => array(
            'mautic.social.monitoring' => array(
                'route'     => 'mautic_social_index',
                'parent'    => 'mautic.core.components',
                'access'    => 'plugin:mauticSocial:monitoring:view',
            )
        )
    ),

    'categories' => array(
        'plugin:mauticSocial' => 'mautic.social.monitoring'
    ),

    'twitter' => array(
        'tweet_request_count' => 100
    ),

    'parameters' => array(
        "twitter_handle_field"   => 'twitter'
    )
);