<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'services' => [
        'events' => [
            'mautic.notification.campaignbundle.subscriber' => [
                'class'     => 'Mautic\NotificationBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.lead',
                    'mautic.notification.model.notification',
                    'mautic.notification.api',
                ],
            ],
            'mautic.notification.pagebundle.subscriber' => [
                'class'     => 'Mautic\NotificationBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.core.js.subscriber' => [
                'class' => 'Mautic\NotificationBundle\EventListener\BuildJsSubscriber',
            ],
            'mautic.notification.notificationbundle.subscriber' => [
                'class'     => 'Mautic\NotificationBundle\EventListener\NotificationSubscriber',
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                ],
            ],
            'mautic.notification.subscriber.channel' => [
                'class'     => \Mautic\NotificationBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.notification.stats.subscriber' => [
                'class'     => \Mautic\NotificationBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.notification' => [
                'class'     => 'Mautic\NotificationBundle\Form\Type\NotificationType',
                'arguments' => 'mautic.factory',
                'alias'     => 'notification',
            ],
            'mautic.form.type.notificationconfig' => [
                'class' => 'Mautic\NotificationBundle\Form\Type\ConfigType',
                'alias' => 'notificationconfig',
            ],
            'mautic.form.type.notificationsend_list' => [
                'class'     => 'Mautic\NotificationBundle\Form\Type\NotificationSendType',
                'arguments' => 'router',
                'alias'     => 'notificationsend_list',
            ],
            'mautic.form.type.notification_list' => [
                'class' => 'Mautic\NotificationBundle\Form\Type\NotificationListType',
                'alias' => 'notification_list',
            ],
        ],
        'helpers' => [
            'mautic.helper.notification' => [
                'class'     => 'Mautic\NotificationBundle\Helper\NotificationHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'notification_helper',
            ],
        ],
        'other' => [
            'mautic.notification.api' => [
                'class'     => 'Mautic\NotificationBundle\Api\OneSignalApi',
                'arguments' => [
                    'mautic.factory',
                    'mautic.http.connector',
                    'mautic.page.model.trackable',
                    'mautic.helper.integration',
                ],
                'alias' => 'notification_api',
            ],
        ],
        'models' => [
            'mautic.notification.model.notification' => [
                'class'     => 'Mautic\NotificationBundle\Model\NotificationModel',
                'arguments' => [
                    'mautic.page.model.trackable',
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_notification_index' => [
                'path'       => '/notifications/{page}',
                'controller' => 'MauticNotificationBundle:Notification:index',
            ],
            'mautic_notification_action' => [
                'path'       => '/notifications/{objectAction}/{objectId}',
                'controller' => 'MauticNotificationBundle:Notification:execute',
            ],
            'mautic_notification_contacts' => [
                'path'       => '/notifications/view/{objectId}/contact/{page}',
                'controller' => 'MauticNotificationBundle:Notification:contacts',
            ],
            'mautic_mobile_notification_index' => [
                'path'       => '/mobile_notifications/{page}',
                'controller' => 'MauticNotificationBundle:MobileNotification:index',
            ],
            'mautic_mobile_notification_action' => [
                'path'       => '/mobile_notifications/{objectAction}/{objectId}',
                'controller' => 'MauticNotificationBundle:MobileNotification:execute',
            ],
            'mautic_mobile_notification_contacts' => [
                'path'       => '/mobile_notifications/view/{objectId}/contact/{page}',
                'controller' => 'MauticNotificationBundle:MobileNotification:contacts',
            ],
        ],
        'public' => [
            'mautic_receive_notification' => [
                'path'       => '/notification/receive',
                'controller' => 'MauticNotificationBundle:Api\NotificationApi:receive',
            ],
            'mautic_subscribe_notification' => [
                'path'       => '/notification/subscribe',
                'controller' => 'MauticNotificationBundle:Api\NotificationApi:subscribe',
            ],
            'mautic_notification_popup' => [
                'path'       => '/notification',
                'controller' => 'MauticNotificationBundle:Popup:index',
            ],

            // JS / Manifest URL's
            'mautic_onesignal_worker' => [
                'path'       => '/OneSignalSDKWorker.js',
                'controller' => 'MauticNotificationBundle:Js:worker',
            ],
            'mautic_onesignal_updater' => [
                'path'       => '/OneSignalSDKUpdaterWorker.js',
                'controller' => 'MauticNotificationBundle:Js:updater',
            ],
            'mautic_onesignal_manifest' => [
                'path'       => '/manifest.json',
                'controller' => 'MauticNotificationBundle:Js:manifest',
            ],
            'mautic_app_notification' => [
                'path'       => '/notification/appcallback',
                'controller' => 'MauticNotificationBundle:AppCallback:index',
            ],
        ],
        'api' => [
            'mautic_api_notificationsstandard' => [
                'standard_entity' => true,
                'name'            => 'notifications',
                'path'            => '/notifications',
                'controller'      => 'MauticNotificationBundle:Api\NotificationApi',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.notification.notifications' => [
                    'route'  => 'mautic_notification_index',
                    'access' => ['notification:notifications:viewown', 'notification:notifications:viewother'],
                    'checks' => [
                        'integration' => [
                            'OneSignal' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'parent'   => 'mautic.core.channels',
                    'priority' => 80,
                ],
                'mautic.notification.mobile_notifications' => [
                    'route'  => 'mautic_mobile_notification_index',
                    'access' => ['notification:mobile_notifications:viewown', 'notification:mobile_notifications:viewother'],
                    'checks' => [
                        'integration' => [
                            'OneSignal' => [
                                'enabled'  => true,
                                'features' => [
                                    'mobile',
                                ],
                            ],
                        ],
                    ],
                    'parent'   => 'mautic.core.channels',
                    'priority' => 80,
                ],
            ],
        ],
    ],
    //'categories' => [
    //    'notification' => null
    //],
    'parameters' => [
        'notification_enabled'              => false,
        'notification_landing_page_enabled' => true,
        'notification_app_id'               => null,
        'notification_rest_api_key'         => null,
        'notification_safari_web_id'        => null,
        'gcm_sender_id'                     => '482941778795',
        'welcomenotification_enabled'       => true,
    ],
];
