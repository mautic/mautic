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
    'name'        => 'Citrix',
    'description' => 'Enables integration with Mautic supported Citrix collaboration products.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_citrix_proxy' => [
                'path'       => '/citrix/proxy',
                'controller' => 'MauticCitrixBundle:Public:proxy',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'MauticCitrixBundle:Public:sessionChanged',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.citrix.formbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.leadbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.emailbundle.subscriber' => [
                'class' => 'MauticPlugin\MauticCitrixBundle\EventListener\EmailSubscriber',
            ],
        ],
        'forms' => [
            'mautic.form.type.fieldslist.citrixlist' => [
                'class' => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixListType',
                'alias' => 'citrix_list',
            ],
            'mautic.form.type.citrix.submitaction' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixActionType',
                'alias'     => 'citrix_submit_action',
                'arguments' => [
                    'mautic.form.model.field',
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixCampaignEventType',
                'alias'     => 'citrix_campaign_event',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\Form\Type\CitrixCampaignActionType',
                'alias'     => 'citrix_campaign_action',
                'arguments' => [
                    'translator',
                ],
            ],
        ],
        'models' => [
            'mautic.citrix.model.citrix' => [
                'class'     => 'MauticPlugin\MauticCitrixBundle\Model\CitrixModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.event',
                ],
            ],
        ],
    ],
];
