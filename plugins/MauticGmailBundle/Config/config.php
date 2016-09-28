<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Gmail',
    'description' => 'Enables integrations with Gmail for email tracking',
    'version'     => '1.0',
    'author'      => 'Werner Garcia',

    'routes'   => array(
        'public' => [
            'mautic_gmail_tracker'             => [
                'path'       => '/gmail/tracking.gif',
                'controller' => 'MauticGmailBundle:Public:trackingImage'
            ]
        ],
        'main' => [
            'mautic_gmail_timeline_index'       => [
                'path'         => '/gmail/timeline/{page}',
                'controller'   => 'MauticGmailBundle:Timeline:index',
            ],
            'mautic_gmail_timeline_login'       => [
                'path'         => '/gmail/timeline/login',
                'controller'   => 'MauticGmailBundle:Timeline:login',
            ],
            'mautic_gmail_timeline_logincheck'       => [
                'path'         => '/gmail/timeline/logincheck',
                'controller'   => 'MauticGmailBundle:Timeline:logincheck',
            ],
            'mautic_gmail_timeline_view'        => [
                'path'         => '/gmail/timeline/view/{leadId}/{page}',
                'controller'   => 'MauticGmailBundle:Timeline:view',
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
        ]
    ),

    'services'    => array(
        'events' => array(
            'mautic.gmail.formbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticGmailBundle\EventListener\FormSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.fieldslist.selectidentifier'  => array(
                'class' => 'MauticPlugin\MauticGmailBundle\Form\Type\FormFieldsType',
                'arguments' => 'mautic.factory',
                'alias' => 'formfields_list'
            )
        )
    )

);
