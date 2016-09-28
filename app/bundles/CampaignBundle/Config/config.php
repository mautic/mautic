<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main' => array(
            'mautic_campaignevent_action' => array(
                'path'       => '/campaigns/events/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Event:execute'
            ),
            'mautic_campaignsource_action'      => array(
                'path'       => '/campaigns/sources/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Source:execute'
            ),
            'mautic_campaign_index'       => array(
                'path'       => '/campaigns/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:index'
            ),
            'mautic_campaign_action'      => array(
                'path'       => '/campaigns/{objectAction}/{objectId}',
                'controller' => 'MauticCampaignBundle:Campaign:execute'
            ),
            'mautic_campaign_contacts'       => array(
                'path'       => '/campaigns/view/{objectId}/contact/{page}',
                'controller' => 'MauticCampaignBundle:Campaign:contacts',
            )
        ),
        'api'  => array(
            'mautic_api_getcampaigns' => array(
                'path'       => '/campaigns',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:getEntities'
            ),
            'mautic_api_getcampaign'  => array(
                'path'       => '/campaigns/{id}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:getEntity'
            ),
            'mautic_api_campaignaddcontact' => array(
                'path'       => '/campaigns/{id}/contact/add/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:addLead',
                'method'     => 'POST'
            ),
            'mautic_api_campaignremovecontact' => array(
                'path'       => '/campaigns/{id}/contact/remove/{leadId}',
                'controller' => 'MauticCampaignBundle:Api\CampaignApi:removeLead',
                'method'     => 'POST'
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'mautic.campaign.menu.index' => array(
                'iconClass' => 'fa-clock-o',
                'route'     => 'mautic_campaign_index',
                'access'    => 'campaign:campaigns:view',
                'priority'  => 50
            )
        )
    ),

    'categories' => array(
        'campaign' => null
    ),

    'services' => array(
        'events' => array(
            'mautic.campaign.subscriber'                => array(
                'class' => 'Mautic\CampaignBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog'
                ]
            ),
            'mautic.campaign.leadbundle.subscriber'     => array(
                'class' => 'Mautic\CampaignBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.lead'
                ]
            ),
            'mautic.campaign.calendarbundle.subscriber' => array(
                'class' => 'Mautic\CampaignBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.campaign.pointbundle.subscriber'    => array(
                'class' => 'Mautic\CampaignBundle\EventListener\PointSubscriber'
            ),
            'mautic.campaign.search.subscriber'         => array(
                'class' => 'Mautic\CampaignBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign'
                ]
            ),
            'mautic.campaign.dashboard.subscriber'           => array(
                'class' => 'Mautic\CampaignBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.campaign.model.event'
                ]
            ),
            'mautic.campaignconfigbundle.subscriber'   => array(
                'class' => 'Mautic\CampaignBundle\EventListener\ConfigSubscriber'
            ),
        ),
        'forms'  => array(
            'mautic.campaign.type.form'                 => array(
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign'
            ),
            'mautic.campaignrange.type.action'          => array(
                'class' => 'Mautic\CampaignBundle\Form\Type\EventType',
                'alias' => 'campaignevent'
            ),
            'mautic.campaign.type.campaignlist'         => array(
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign_list'
            ),
            'mautic.campaign.type.trigger.leadchange'   => array(
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventLeadChangeType',
                'alias' => 'campaignevent_leadchange'
            ),
            'mautic.campaign.type.action.addremovelead' => array(
                'class' => 'Mautic\CampaignBundle\Form\Type\CampaignEventAddRemoveLeadType',
                'alias' => 'campaignevent_addremovelead'
            ),
            'mautic.campaign.type.canvassettings'       => array(
                'class' => 'Mautic\CampaignBundle\Form\Type\EventCanvasSettingsType',
                'alias' => 'campaignevent_canvassettings'
            ),
            'mautic.campaign.type.leadsource'           => array(
                'class'     => 'Mautic\CampaignBundle\Form\Type\CampaignLeadSourceType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaign_leadsource'
            ),
            'mautic.form.type.campaignconfig'                    => array(
                'class'     => 'Mautic\CampaignBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignconfig'
            ),
        ),
        'models' =>  array(
            'mautic.campaign.model.campaign' => array(
                'class' => 'Mautic\CampaignBundle\Model\CampaignModel',
                'arguments' => array(
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.list',
                    'mautic.form.model.form'
                )
            ),
            'mautic.campaign.model.event' => array(
                'class' => 'Mautic\CampaignBundle\Model\EventModel',
                'arguments' => array(
                    'mautic.helper.ip_lookup',
                    'mautic.helper.core_parameters',
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.factory'
                )
            )
        )
    ),
    'parameters' => array(
        'campaign_time_wait_on_event_false' => 'PT1H'
    )
);
