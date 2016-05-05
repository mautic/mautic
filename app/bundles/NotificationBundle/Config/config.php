<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'services'    => array(
        'events'  => array(
            'mautic.notification.campaignbundle.subscriber' => array(
                'class' => 'Mautic\NotificationBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.notification.configbundle.subscriber' => array(
                'class' => 'Mautic\NotificationBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.notification.pagebundle.subscriber' => array(
                'class' => 'Mautic\NotificationBundle\EventListener\PageSubscriber',
                'arguments' => 'mautic.factory'
            ),
            'mautic.core.js.subscriber'           => array(
                'class' => 'Mautic\NotificationBundle\EventListener\BuildJsSubscriber'
            )
        ),
        'forms' => array(
            'mautic.form.type.notification' => array(
                'class' => 'Mautic\NotificationBundle\Form\Type\NotificationType',
                'arguments' => 'mautic.factory',
                'alias' => 'notification'
            ),
            'mautic.form.type.notificationconfig'  => array(
                'class' => 'Mautic\NotificationBundle\Form\Type\ConfigType',
                'alias' => 'notificationconfig'
            ),
            'mautic.form.type.notificationsend_list' => array(
                'class'     => 'Mautic\NotificationBundle\Form\Type\NotificationSendType',
                'arguments' => 'router',
                'alias'     => 'notificationsend_list'
            ),
            'mautic.form.type.notification_list'     => array(
                'class'     => 'Mautic\NotificationBundle\Form\Type\NotificationListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'notification_list'
            ),
        ),
        'helpers' => array(
            'mautic.helper.notification' => array(
                'class'     => 'Mautic\NotificationBundle\Helper\NotificationHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'notification_helper'
            )
        ),
        'other' => array(
            'mautic.notification.api' => array(
                'class'     => 'Mautic\NotificationBundle\Api\OneSignalApi',
                'arguments' => array(
                    'mautic.factory',
                    'mautic.http.connector'
                ),
                'alias' => 'notification_api'
            )
        )
    ),
    'routes' => array(
        'main'   => array(
            'mautic_notification_index'  => array(
                'path'       => '/notifications/{page}',
                'controller' => 'MauticNotificationBundle:Notification:index'
            ),
            'mautic_notification_action' => array(
                'path'       => '/notifications/{objectAction}/{objectId}',
                'controller' => 'MauticNotificationBundle:Notification:execute'
            )
        ),
        'public' => array(
            'mautic_receive_notification' => array(
                'path'       => '/notification/receive',
                'controller' => 'MauticNotificationBundle:Api\NotificationApi:receive'
            ),
            'mautic_subscribe_notification' => array(
                'path'       => '/notification/subscribe',
                'controller' => 'MauticNotificationBundle:Api\NotificationApi:subscribe'
            ),
            'mautic_notification_popup' => array(
                'path'       => '/notification',
                'controller' => 'MauticNotificationBundle:Popup:index'
            ),

            // JS / Manifest URL's
            'mautic_onesignal_worker' => array(
                'path'       => '/OneSignalSDKWorker.js',
                'controller' => 'MauticNotificationBundle:Js:worker'
            ),
            'mautic_onesignal_updater' => array(
                'path'       => '/OneSignalSDKUpdaterWorker.js',
                'controller' => 'MauticNotificationBundle:Js:updater'
            ),
            'mautic_onesignal_manifest' => array(
                'path'       => '/manifest.json',
                'controller' => 'MauticNotificationBundle:Js:manifest',
            )
        )
    ),
    'menu'       => array(
        'main' => array(
            'items'    => array(
                'mautic.notification.notifications' => array(
                    'route'     => 'mautic_notification_index',
                    'access'    => array('notification:notifications:viewown', 'notification:notifications:viewother'),
                    'checks'    => array(
                        'parameters' => array(
                            'notification_enabled' => true
                        )
                    ),
                    'parent'    => 'mautic.core.channels'
                )
            )
        )
    ),
    //'categories' => array(
    //    'notification' => null
    //),
    'parameters' => array(
        'notification_enabled' => false,
        'notification_app_id' => null,
        'notification_rest_api_key' => null,
        'notification_safari_web_id' => null
    )
);