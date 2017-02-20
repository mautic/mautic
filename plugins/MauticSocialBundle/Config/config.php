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
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetType',
                'alias' => 'twitter_tweet',
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
        ],
        'models' => [
            'mautic.social.model.monitoring' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Model\MonitoringModel',
            ],
            'mautic.social.model.postcount' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Model\PostCountModel',
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
