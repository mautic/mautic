<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name' => 'Unsubscribe',
    'description' => 'This plugin creates a unsubscribe form for campaigns together with a custom campaign decision',
    'author' => 'Partout D.N.A.',
    'version' => '0.0.1',
    'menu' => array(
        'admin' => array(
            'items' => array(
                'plugin.unsubscribe_campaign_name.index' => array(
                    'priority' => 9,
                    'id' => 'plugin_unsubscribe_campaign_name_index',
                    'iconClass' => 'fa-clock-o',
                    'route' => 'plugin_unsubscribe_campaign_name_index'
                )
            ),
        ),
    ),
    'routes' => array(
        'main' => array(
            'plugin_unsubscribe_campaign_name_index' => array(
                'path' => 'unsubscribe/campaign-name',
                'controller' => 'CampaignUnsubscribeBundle:CampaignName:index'
            ),
            'plugin_unsubscribe_new_campaign_name' => array(
                'path' => '/unsubscribe/new-field',
                'controller' => 'CampaignUnsubscribeBundle:CampaignName:new'
            ),
            'mautic_unsubscribe_campaign_names_action' => array(
                'path' => '/unsubscribe/action/{objectAction}/{objectId}',
                'controller' => 'CampaignUnsubscribeBundle:CampaignName:execute'
            )
        ),
        'public' => array(
            'unsubscribe_thanks' => array(
                'path' => 'unsubscribe/thanks/{idHash}',
                'controller' => 'CampaignUnsubscribeBundle:Default:thanks'
            ),
            'unsubscribe' => array(
                'path' => '/unsubscribe/{idHash}',
                'controller' => 'CampaignUnsubscribeBundle:Default:unsubscribe'
            ),
        ),
    ),
    'services' => array(
        'events' => array(
            'plugin.campaignunsubscribe.campaignbundle.subscriber' => array(
                'class' => 'MauticPlugin\CampaignUnsubscribeBundle\EventListener\CampaignSubscriber',
            ),
            'plugin.campaignunsubscribe.emailbundle.subscriber' => array(
                'class' => 'MauticPlugin\CampaignUnsubscribeBundle\EventListener\EmailSubscriber',
            ),
            'plugin.campaignunsubscribe.configbundle.subscriber' => array(
                'class' => 'MauticPlugin\CampaignUnsubscribeBundle\EventListener\ConfigSubscriber',
            )
        ),
        'forms' => [
            'mautic.campaignunsubscribe.config' => [
                'class' => 'MauticPlugin\CampaignUnsubscribeBundle\Form\Type\ConfigType',
                'alias' => 'campaign_unsubscribe_config'
            ],
            'plugin.form.type.campaign_name' => [
                'class' => 'MauticPlugin\CampaignUnsubscribeBundle\Form\Type\CampaignNameType',
                'alias' => 'campaign_name',
            ]
        ]
    ),
    'parameters' => array(
        'campaign_unsubscribe_remove_campaign_donotcontact' => true,
        'campaign_unsubscribe_message_title' => '',
        'campaign_unsubscribe_message_body' => '',
        'campaign_unsubscribe_confirmation_title' => '',
        'campaign_unsubscribe_confirmation_body' => '',
        'campaign_unsubscribe_campaign_list_label' => '',
        'campaign_unsubscribe_campaign_list_none_label' => '',
        'campaign_unsubscribe_donotcontact_label' => '',
        'campaign_unsubscribe_submit_label' => '',
        'campaign_unsubscribe_logo_url' => ''
    )
);