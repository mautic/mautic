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
        'public' => [
            'mautic_social_js_generate' => [
                'path'       => '/social/generate/{formName}.js',
                'controller' => 'MauticSocialBundle:Js:generate',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.social.formbundle.subscriber' => [
                'class' => \MauticPlugin\MauticSocialBundle\EventListener\FormSubscriber::class,
            ],
            'mautic.social.campaignbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticSocialBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.social.helper.campaign',
                    'mautic.helper.integration',
                    'translator',
                ],
            ],
            'mautic.social.configbundle.subscriber' => [
                'class' => \MauticPlugin\MauticSocialBundle\EventListener\ConfigSubscriber::class,
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
                    'mautic.security',
                    'doctrine.orm.entity_manager',
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
            ],
            'mautic.form.type.social.facebook' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\FacebookType',
            ],
            'mautic.form.type.social.twitter' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterType',
            ],
            'mautic.form.type.social.linkedin' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\LinkedInType',
            ],
            'mautic.social.form.type.twitter.tweet' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetType',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.social.form.type.monitoring' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\MonitoringType',
                'arguments' => [
                    'mautic.social.model.monitoring',
                ],
            ],
            'mautic.social.form.type.network.twitter.abstract' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterAbstractType',
            ],
            'mautic.social.form.type.network.twitter.hashtag' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterHashtagType',
            ],
            'mautic.social.form.type.network.twitter.mention' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterMentionType',
            ],
            'mautic.social.form.type.network.twitter.custom' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterCustomType',
            ],
            'mautic.social.config' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.lead.model.field',
            ],
            'mautic.social.tweet.list' => [
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetListType',
            ],
            'mautic.social.tweetsend_list' => [
                'class'     => 'MauticPlugin\MauticSocialBundle\Form\Type\TweetSendType',
                'arguments' => 'router',
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
            'mautic.social.helper.twitter_command' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Helper\TwitterCommandHelper::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field',
                    'mautic.social.model.monitoring',
                    'mautic.social.model.postcount',
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.facebook' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\FacebookIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.integration.foursquare' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.integration.instagram' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.integration.linkedin' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\LinkedInIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.integration.twitter' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.helper.integration',
                ],
            ],
        ],
        'command' => [
            'mautic.social.command.twitter_hashtags' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Command\MonitorTwitterHashtagsCommand::class,
                'arguments' => [
                    'event_dispatcher',
                    'translator',
                    'mautic.helper.integration',
                    'mautic.social.helper.twitter_command',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.social.command.twitter_mentions' => [
                'class'     => \MauticPlugin\MauticSocialBundle\Command\MonitorTwitterMentionsCommand::class,
                'arguments' => [
                    'event_dispatcher',
                    'translator',
                    'mautic.helper.integration',
                    'mautic.social.helper.twitter_command',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'mautic.social.monitoring' => [
                'route'    => 'mautic_social_index',
                'parent'   => 'mautic.core.channels',
                'access'   => 'mauticSocial:monitoring:view',
                'priority' => 0,
            ],
            'mautic.social.tweets' => [
                'route'    => 'mautic_tweet_index',
                'access'   => ['mauticSocial:tweets:viewown', 'mauticSocial:tweets:viewother'],
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
