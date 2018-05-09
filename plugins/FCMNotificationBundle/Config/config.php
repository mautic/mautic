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
            'mauticplugin.fcmnotification.campaignbundle.subscriber' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.lead',
                    'mauticplugin.fcmnotification.notification.model.notification',
                    'mauticplugin.fcmnotification.notification.api',
                ],
            ],
            'mauticplugin.fcmnotification.pagebundle.subscriber' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.helper.integration',
                ],
            ],
            'mauticplugin.fcmnotification.core.js.subscriber' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\EventListener\BuildJsSubscriber',
                'arguments' => [
                    'mauticplugin.fcmnotification.helper.notification',
                    'mautic.helper.integration',
                ],
            ],
            'mauticplugin.fcmnotification.notificationbundle.subscriber' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\EventListener\NotificationSubscriber',
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.helper.integration',
                ],
            ],
//            Left out until 2.9
//            'mauticplugin.fcmnotification.notification.subscriber.form' => [
//                'class'     => \MauticPlugin\FCMNotificationBundle\EventListener\FormSubscriber::class,
//                'arguments' => [
//                    'mautic.helper.integration',
//                    'mautic.lead.model.lead',
//                    'mauticplugin.fcmnotification.notification.model.notification',
//                    'mauticplugin.fcmnotification.notification.api',
//                ],
//            ],
            'mauticplugin.fcmnotification.subscriber.channel' => [
                'class'     => \MauticPlugin\FCMNotificationBundle\EventListener\ChannelSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mauticplugin.fcmnotification.stats.subscriber' => [
                'class'     => \MauticPlugin\FCMNotificationBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.helper.integration',
                ],
            ],
            'mauticplugin.fcmnotification.mobile_notification.report.subscriber' => [
                'class'     => \MauticPlugin\FCMNotificationBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                    'mautic.lead.model.company_report_data',
                    'mautic.helper.integration',
                ],
            ],
        ],
        'forms' => [
            'mauticplugin.fcmnotification.form.type.notification' => [
                'class' => 'Mautic\NotificationBundle\Form\Type\NotificationType',
                'alias' => 'notification',
            ],
            'mauticplugin.fcmnotification.form.type.mobile.notification' => [
                'class' => \Mautic\NotificationBundle\Form\Type\MobileNotificationType::class,
                'alias' => 'mobile_notification',
            ],
            'mauticplugin.fcmnotification.form.type.mobile.notification_details' => [
                'class'     => \Mautic\NotificationBundle\Form\Type\MobileNotificationDetailsType::class,
                'arguments' => [
                    'mauticplugin.fcmnotification.helper.notification',
                ],
                'alias' => 'mobile_notification_details',
            ],
            'mauticplugin.fcmnotification.form.type.notificationconfig' => [
                'class' => 'Mautic\NotificationBundle\Form\Type\ConfigType',
                'alias' => 'notificationconfig',
            ],
            'mauticplugin.fcmnotification.form.type.notificationsend_list' => [
                'class'     => 'Mautic\NotificationBundle\Form\Type\NotificationSendType',
                'arguments' => 'router',
                'alias'     => 'notificationsend_list',
            ],
            'mauticplugin.fcmnotification.form.type.notification_list' => [
                'class' => 'Mautic\NotificationBundle\Form\Type\NotificationListType',
                'alias' => 'notification_list',
            ],
            'mauticplugin.fcmnotification.form.type.mobilenotificationsend_list' => [
                'class'     => \Mautic\NotificationBundle\Form\Type\MobileNotificationSendType::class,
                'arguments' => 'router',
                'alias'     => 'mobilenotificationsend_list',
            ],
            'mauticplugin.fcmnotification.form.type.mobilenotification_list' => [
                'class' => \Mautic\NotificationBundle\Form\Type\MobileNotificationListType::class,
                'alias' => 'mobilenotification_list',
            ],
        ],
        'helpers' => [
            'mauticplugin.fcmnotification.helper.notification' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\Helper\NotificationHelper',
                'alias'     => 'notification_helper',
                'arguments' => [
                    'mautic.factory',
                    'templating.helper.assets',
                    'mautic.helper.core_parameters',
                    'mautic.helper.integration',
                    'router',
                    'request_stack',
                ],
            ],
        ],
        'other' => [
            'mauticplugin.fcmnotification.notification.api' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\Api\FCMApi',
                'arguments' => [
                    'mautic.factory',
                    'mautic.http.connector',
                    'mautic.page.model.trackable',
                    'mautic.helper.integration',
                ],
                'alias' => 'fcmnotification_api',
            ],
        ],
        'models' => [
            'mauticplugin.fcmnotification.notification.model.notification' => [
                'class'     => 'MauticPlugin\FCMNotificationBundle\Model\NotificationModel',
                'arguments' => [
                    'mautic.page.model.trackable',
                ],
            ],
        ],
        'integrations' => [
            'mauticplugin.fcmnotification.integration.fcm' => [
                'class' => \MauticPlugin\FCMNotificationBundle\Integration\FCMIntegration::class,
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'mautic_notification_index' => [
                'path'       => '/notifications/{page}',
                'controller' => 'FCMNotificationBundle:Notification:index',
            ],
            'mautic_notification_action' => [
                'path'       => '/notifications/{objectAction}/{objectId}',
                'controller' => 'FCMNotificationBundle:Notification:execute',
            ],
            'mautic_notification_contacts' => [
                'path'       => '/notifications/view/{objectId}/contact/{page}',
                'controller' => 'FCMNotificationBundle:Notification:contacts',
            ],
            'mautic_mobile_notification_index' => [
                'path'       => '/mobile_notifications/{page}',
                'controller' => 'FCMNotificationBundle:MobileNotification:index',
            ],
            'mautic_mobile_notification_action' => [
                'path'       => '/mobile_notifications/{objectAction}/{objectId}',
                'controller' => 'FCMNotificationBundle:MobileNotification:execute',
            ],
            'mautic_mobile_notification_contacts' => [
                'path'       => '/mobile_notifications/view/{objectId}/contact/{page}',
                'controller' => 'FCMNotificationBundle:MobileNotification:contacts',
            ],
        ],
        'public' => [
            'mautic_receive_notification' => [
                'path'       => '/notification/receive',
                'controller' => 'FCMNotificationBundle:Api\NotificationApi:receive',
            ],
            'mautic_subscribe_notification' => [
                'path'       => '/notification/subscribe',
                'controller' => 'FCMNotificationBundle:Api\NotificationApi:subscribe',
            ],
            'mautic_track_notification_open' => [
                'path'       => '/notification/trackopen',
                'controller' => 'FCMNotificationBundle:Api\NotificationApi:trackopen',
            ],
            'mautic_notification_popup' => [
                'path'       => '/notification',
                'controller' => 'FCMNotificationBundle:Popup:index',
            ],
            'mautic_notification_test' => [
                'path'       => '/notificationTest',
                'controller' => 'FCMNotificationBundle:Popup:test',
            ], 

            // JS / Manifest URL's
            'mautic_fcm_worker' => [
                'path'       => '/firebase-messaging-sw.js',
                'controller' => 'FCMNotificationBundle:Js:worker',
            ],            
            'mautic_fcm_manifest' => [
                'path'       => '/manifest.json',
                'controller' => 'FCMNotificationBundle:Js:manifest',
            ],
            'mautic_app_notification' => [
                'path'       => '/notification/appcallback',
                'controller' => 'FCMNotificationBundle:AppCallback:index',
            ],
        ],
        'api' => [
            'mautic_api_notificationsstandard' => [
                'standard_entity' => true,
                'name'            => 'notifications',
                'path'            => '/notifications',
                'controller'      => 'FCMNotificationBundle:Api\NotificationApi',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.plugin.fcmnotification.notifications' => [
                    'route'  => 'mautic_notification_index',
                    'access' => ['notification:notifications:viewown', 'notification:notifications:viewother'],
                    'checks' => [
                        'integration' => [
                            'FCM' => [
                                'enabled' => true,
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
        'notification_enabled'               => false,
        'notification_landing_page_enabled'  => true,
        'notification_tracking_page_enabled' => false,
        'notification_app_id'                => null,
        'notification_rest_api_key'          => null,
        'notification_safari_web_id'         => null,
        'gcm_sender_id'                      => '103953800507',
        'notification_subdomain_name'        => null,
        'welcomenotification_enabled'        => true,
    ],
];
