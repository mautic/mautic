<?php

declare(strict_types=1);

return [
    'name'        => 'Social Media',
    'description' => 'Enables integrations with Mautic supported social media services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'main' => [
            'mautic_social_index' => [
                'path'       => '/monitoring/{page}',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::indexAction',
            ],
            'mautic_social_action' => [
                'path'       => '/monitoring/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::executeAction',
            ],
            'mautic_social_contacts' => [
                'path'       => '/monitoring/view/{objectId}/contacts/{page}',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\MonitoringController::contactsAction',
            ],
            'mautic_tweet_index' => [
                'path'       => '/tweets/{page}',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\TweetController::indexAction',
            ],
            'mautic_tweet_action' => [
                'path'       => '/tweets/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\TweetController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_tweetsstandard' => [
                'standard_entity' => true,
                'name'            => 'tweets',
                'path'            => '/tweets',
                'controller'      => MauticPlugin\MauticSocialBundle\Controller\Api\TweetApiController::class,
            ],
        ],
        'public' => [
            'mautic_social_js_generate' => [
                'path'       => '/social/generate/{formName}.js',
                'controller' => 'MauticPlugin\MauticSocialBundle\Controller\JsController::generateAction',
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
                'checks'   => [
                    'integration' => [
                        'Twitter' => [
                            'enabled' => true,
                        ],
                    ],
                ],
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
        'plugin:mauticSocial' => [
            'label' => 'mautic.social.monitoring',
            'class' => MauticPlugin\MauticSocialBundle\Entity\Monitoring::class,
        ],
    ],

    'twitter' => [
        'tweet_request_count' => 100,
    ],

    'parameters' => [
        'twitter_handle_field' => 'twitter',
    ],
];
