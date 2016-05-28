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
                'class' => 'Mautic\SmsBundle\EventListener\SmsSubscriber'
            )
        ),
        'forms' => array(
            'mautic.form.type.sms' => array(
                'class'     => 'Mautic\SmsBundle\Form\Type\SmsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'sms'
            ),
            'mautic.form.type.smsconfig'  => array(
                'class' => 'Mautic\SmsBundle\Form\Type\ConfigType',
                'alias' => 'smsconfig'
            ),
            'mautic.form.type.smssend_list' => array(
                'class'     => 'Mautic\SmsBundle\Form\Type\SmsSendType',
                'arguments' => 'router',
                'alias'     => 'smssend_list'
            ),
            'mautic.form.type.sms_list'     => array(
                'class'     => 'Mautic\SmsBundle\Form\Type\SmsListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'sms_list'
            ),
        ),
        'helpers' => array(
            'mautic.helper.sms' => array(
                'class'     => 'Mautic\SmsBundle\Helper\SmsHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'sms_helper'
            )
        ),
        'other' => array(
            'mautic.sms.api' => array(
                'class'     => 'Mautic\SmsBundle\Api\TwilioApi',
                'arguments' => array(
                    'mautic.factory',
                    'mautic.twilio.service',
                    '%mautic.sms_sending_phone_number%'
                ),
                'alias' => 'sms_api'
            ),
            'mautic.twilio.service' => array(
                'class'     => 'Services_Twilio',
                'arguments' => array(
                    '%mautic.sms_username%',
                    '%mautic.sms_password%'
                ),
                'alias' => 'twilio_service'
            )
        )
    ),
    'routes' => array(
        'main'   => array(
            'mautic_sms_index'  => array(
                'path'       => '/sms/{page}',
                'controller' => 'MauticSmsBundle:Sms:index'
            ),
            'mautic_sms_action' => array(
                'path'       => '/sms/{objectAction}/{objectId}',
                'controller' => 'MauticSmsBundle:Sms:execute'
            )
        ),
        'public' => array(
            'mautic_receive_sms' => array(
                'path'       => '/sms/receive',
                'controller' => 'MauticSmsBundle:Api\SmsApi:receive'
            )
        )
    ),
    'menu'       => array(
        'main' => array(
            'items'    => array(
                'mautic.sms.smses' => array(
                    'route'     => 'mautic_sms_index',
                    'access'    => array('sms:smses:viewown', 'sms:smses:viewother'),
                    'parent'    => 'mautic.core.channels',
                    'checks'    => array(
                        'parameters' => array(
                            'sms_enabled' => true
                        )
                    )
                )
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