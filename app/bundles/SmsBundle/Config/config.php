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
            'mautic.sms.campaignbundle.subscriber' => array(
                'class' => 'Mautic\SmsBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.sms.configbundle.subscriber' => array(
                'class' => 'Mautic\SmsBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.sms.smsbundle.subscriber' => array(
                'class' => 'Mautic\SmsBundle\EventListener\SmsSubscriber',
                'arguments' => 'mautic.http.connector'
            )
        ),
        'forms' => array(
            'mautic.form.type.sms' => array(
                'class' => 'Mautic\SmsBundle\Form\Type\SmsType',
                'alias' => 'sms'
            ),
            'mautic.form.type.smsconfig'  => array(
                'class' => 'Mautic\SmsBundle\Form\Type\ConfigType',
                'alias' => 'smsconfig'
            )
        ),
        'helpers' => array(
            'mautic.helper.sms' => array(
                'class'     => 'Mautic\SmsBundle\Helper\SmsHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'sms_helper'
            )
        )
    ),
    'routes' => array(
        'public' => array(
            'mautic_receive_sms' => array(
                'path'       => '/sms/receive',
                'controller' => 'MauticSmsBundle:Api\SmsApi:receive'
            )
        )
    ),
    'parameters' => array(
        'sms_enabled' => false,
        'sms_username' => null,
        'sms_password' => null,
        'sms_sending_phone_number' => null
    )
);