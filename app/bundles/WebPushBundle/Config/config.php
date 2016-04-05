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
            'mautic.webpush.campaignbundle.subscriber' => array(
                'class' => 'Mautic\WebPushBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.webpush.configbundle.subscriber' => array(
                'class' => 'Mautic\WebPushBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.webpush.pagebundle.subscriber' => array(
                'class' => 'Mautic\WebPushBundle\EventListener\PageSubscriber',
                'arguments' => 'mautic.factory'
            ),
        ),
        'forms' => array(
            'mautic.form.type.webpush' => array(
                'class' => 'Mautic\WebPushBundle\Form\Type\WebPushType',
                'alias' => 'webpush'
            ),
            'mautic.form.type.webpushconfig'  => array(
                'class' => 'Mautic\WebPushBundle\Form\Type\ConfigType',
                'alias' => 'webpushconfig'
            )
        ),
        'helpers' => array(
            'mautic.helper.webpush' => array(
                'class'     => 'Mautic\WebPushBundle\Helper\WebPushHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'webpush_helper'
            )
        ),
        'other' => array(
            'mautic.webpush.api' => array(
                'class'     => 'Mautic\WebPushBundle\Api\OneSignalApi',
                'arguments' => array(
                    'mautic.factory',
                    'mautic.http.connector'
                ),
                'alias' => 'webpush_api'
            )
        )
    ),
    'routes' => array(
        'public' => array(
            'mautic_receive_webpush' => array(
                'path'       => '/webpush/receive',
                'controller' => 'MauticWebPushBundle:Api\WebPushApi:receive'
            ),
            'mautic_subscribe_webpush' => array(
                'path'       => '/webpush/subscribe',
                'controller' => 'MauticWebPushBundle:Api\WebPushApi:subscribe'
            ),
            'mautic_onesignal_worker' => array(
                'path'       => '/OneSignalSDKWorker.js',
                'controller' => 'MauticWebPushBundle:Js:worker'
            ),
            'mautic_onesignal_updater' => array(
                'path'       => '/OneSignalSDKUpdaterWorker.js',
                'controller' => 'MauticWebPushBundle:Js:updater'
            ),
            'mautic_onesignal_manifest' => array(
                'path'       => '/manifest.json',
                'controller' => 'MauticWebPushBundle:Js:manifest',
            )
        )
    ),
    'parameters' => array(
        'webpush_enabled' => false,
        'webpush_app_id' => null,
        'webpush_rest_api_key' => null
    )
);