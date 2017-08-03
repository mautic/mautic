<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Social Media',
    'description' => 'Enables integrations with Mautic supported social media services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'main' => [
            'mautic_social_index' => [
                'path'       => '/monitoring/{page}',
                'controller' => 'MauticSocialBundle:Monitoring:index',
            ],
            'mautic_social_action' => [
                'path'       => '/monitoring/{objectAction}/{objectId}',
                'controller' => 'MauticSocialBundle:Monitoring:execute',
            ],
            'mautic_social_contacts' => [
                'path'       => '/monitoring/view/{objectId}/contacts/{page}',
                'controller' => 'MauticSocialBundle:Monitoring:contacts',
            ],
            'mautic_tweet_index' => [
                'path'       => '/tweets/{page}',
                'controller' => 'MauticSocialBundle:Tweet:index',
            ],
            'mautic_tweet_action' => [
                'path'       => '/tweets/{objectAction}/{objectId}',
                'controller' => 'MauticSocialBundle:Tweet:execute',
            ],
        ],
        'api' => [
            'mautic_api_tweetsstandard' => [
                'standard_entity' => true,
                'name'            => 'tweets',
                'path'            => '/tweets',
                'controller'      => 'MauticSocialBundle:Api\TweetApi',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.social.formbundle.subscriber' => [
                'class' => 'MauticPlugin\MauticSocialBundle\EventListener\FormSubscriber',
            ],
            'mautic.social.campaignbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.social.helper.campaign',
                    'session',
                ],
            ],
            'mautic.social.configbundle.subscriber' => [
                'class' => 'MauticPlugin\MauticSocialBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.social.subscriber.channel' => [
                'class'     => \MauticPlugin\MauticSocialBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.social.stats.subscriber' => [
                'class'     => \MauticPlugin\MauticSocialBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.social.buildjs.subscriber' => [
                'class'     => \MauticPlugin\MauticSocialBundle\EventListener\BuildJsSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.helper.integration',
                    'session',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.social.sociallogin' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\SocialLoginType',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.form.model.form',
                    'mautic.helper.core_parameters',
                    ],
                'alias' => 'sociallogin',
            ],
            'mautic.form.type.social.facebook' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\FacebookType',
                'alias' => 'socialmedia_facebook',
            ],
            'mautic.form.type.social.twitter' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterType',
                'alias' => 'socialmedia_twitter',
            ],
            'mautic.form.type.social.googleplus' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\GooglePlusType',
                'alias' => 'socialmedia_googleplus',
            ],
            'mautic.form.type.social.linkedin' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\LinkedInType',
                'alias' => 'socialmedia_linkedin',
            ],
            'mautic.social.form.type.twitter.tweet' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetType',
                'alias'     => 'twitter_tweet',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.social.form.type.monitoring' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\MonitoringType',
                'alias' => 'monitoring',
            ],
            'mautic.social.form.type.network.twitter.abstract' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterAbstractType',
                'alias' => 'twitter_abstract',
            ],
            'mautic.social.form.type.network.twitter.hashtag' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterHashtagType',
                'alias' => 'twitter_hashtag',
            ],
            'mautic.social.form.type.network.twitter.mention' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterMentionType',
                'alias' => 'twitter_handle',
            ],
            'mautic.social.form.type.network.twitter.custom' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterCustomType',
                'alias' => 'twitter_custom',
            ],
            'mautic.social.config' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\ConfigType',
                'alias'     => 'social_config',
                'arguments' => 'mautic.lead.model.field',
            ],
            'mautic.social.tweet.list' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetListType',
                'alias' => 'tweet_list',
            ],
            'mautic.social.tweetsend_list' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetSendType',
                'arguments' => 'router',
                'alias'     => 'tweetsend_list',
            ],
            'mautic.social.facebook.pixel.send' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\FacebookPixelSendType',
                'alias' => 'facebook_pixel_send_action',
            ],
        ],
        'models' => [
            'mautic.social.model.monitoring' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Model\MonitoringModel',
            ],
            'mautic.social.model.postcount' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Model\PostCountModel',
            ],
            'mautic.social.model.tweet' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Model\TweetModel',
            ],
        ],
        'others' => [
            'mautic.social.helper.campaign' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.social.model.tweet',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.facebook' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\FacebookIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.foursquare' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.googleplus' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\GooglePlusIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.instagram' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.linkedin' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\LinkedInIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.twitter' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration::class,
                'arguments' => [

                ],
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'mautic.social.monitoring' => [
                'route'    => 'mautic_social_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'plugin:mauticSocial:monitoring:view',
                'priority' => 0,
            ],
            'mautic.social.tweets' => [
                'route'    => 'mautic_tweet_index',
                'access'   => ['plugin:mauticSocial:tweets:viewown', 'plugin:mauticSocial:tweets:viewother'],
                'parent'   => 'mautic.core.channels',
                'priority' => 80,
                'checks'   => [
                    'integration' => [
                        'Twitter' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'plugin:mauticSocial' => 'mautic.social.monitoring',
    ],

    'twitter' => [
        'tweet_request_count' => 100,
    ],

    'parameters' => [
        'twitter_handle_field' => 'twitter',
    ],
];
