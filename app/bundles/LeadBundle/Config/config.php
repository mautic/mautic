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
            'mautic_contact_emailtoken_index' => array(
                'path'       => '/contacts/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_segment_index'        => array(
                'path'       => '/segments/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ),
            'mautic_segment_action'       => array(
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ),
            'mautic_contactfield_index'       => array(
                'path'       => '/contacts/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ),
            'mautic_contactfield_action'      => array(
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ),
            'mautic_contact_index'            => array(
                'path'       => '/contacts/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ),
            'mautic_contactnote_index'        => array(
                'path'         => '/contacts/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => array(
                    'leadId' => 0
                ),
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_contactnote_action'       => array(
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_contact_action'           => array(
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            ),

            // @todo remove in 2.0 - left here till core references are all updated
            'mautic_lead_emailtoken_index' => array(
                'path'       => '/contacts/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_leadlist_index'        => array(
                'path'       => '/segments/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ),
            'mautic_leadlist_action'       => array(
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ),
            'mautic_leadfield_index'       => array(
                'path'       => '/contacts/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ),
            'mautic_leadfield_action'      => array(
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ),
            'mautic_lead_index'            => array(
                'path'       => '/contacts/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ),
            'mautic_leadnote_index'        => array(
                'path'         => '/contacts/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => array(
                    'leadId' => 0
                ),
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_leadnote_action'       => array(
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_lead_action'           => array(
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            ),

            // @deprecated to be removed in 2.0; left here just in case a URL is hardcoded
            'mautic_lead_emailtoken_index_bc' => array(
                'path'       => '/leads/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_leadlist_index_bc'        => array(
                'path'       => '/leads/lists/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ),
            'mautic_leadlist_action_bc'       => array(
                'path'       => '/leads/lists/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ),
            'mautic_leadfield_index_bc'       => array(
                'path'       => '/leads/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ),
            'mautic_leadfield_action_bc'      => array(
                'path'       => '/leads/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ),
            'mautic_lead_index_bc'            => array(
                'path'       => '/leads/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ),
            'mautic_leadnote_index_bc'        => array(
                'path'         => '/leads/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => array(
                    'leadId' => 0
                ),
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_leadnote_action_bc'       => array(
                'path'         => '/leads/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_lead_action_bc'           => array(
                'path'       => '/leads/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            )
        ),
        'api'  => array(
            'mautic_api_getcontacts'          => array(
                'path'       => '/contacts',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ),
            'mautic_api_newcontact'           => array(
                'path'       => '/contacts/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ),
            'mautic_api_getcontact'           => array(
                'path'       => '/contacts/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ),
            'mautic_api_editputcontact'       => array(
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ),
            'mautic_api_editpatchcontact'     => array(
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ),
            'mautic_api_deletecontact'        => array(
                'path'       => '/contacts/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ),
            'mautic_api_getcontactnotes'     => array(
                'path'       => '/contacts/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ),
            'mautic_api_getcontactcampaigns' => array(
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ),
            'mautic_api_getsegments'     => array(
                'path'       => '/segments/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ),
            'mautic_api_getcontactowners'     => array(
                'path'       => '/contacts/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ),
            'mautic_api_getcontactfields'     => array(
                'path'       => '/contacts/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ),
            'mautic_api_getcontactsegments'      => array(
                'path'       => '/contacts/list/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_getsegments'          => array(
                'path'       => '/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_segmentaddcontact'       => array(
                'path'       => '/segments/{id}/contact/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ),
            'mautic_api_segmentremovecontact'    => array(
                'path'       => '/segments/{id}/contact/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            ),

            // @deprecated to be removed in 2.0; left here till routes have been renamed
            'mautic_api_getleads'          => array(
                'path'       => '/contacts',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ),
            'mautic_api_newlead'           => array(
                'path'       => '/contacts/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ),
            'mautic_api_getlead'           => array(
                'path'       => '/contacts/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ),
            'mautic_api_editputlead'       => array(
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ),
            'mautic_api_editpatchlead'     => array(
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ),
            'mautic_api_deletelead'        => array(
                'path'       => '/contacts/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ),
            'mautic_api_getleadsnotes'     => array(
                'path'       => '/contacts/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ),
            'mautic_api_getleadscampaigns' => array(
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ),
            'mautic_api_getleadslists'     => array(
                'path'       => '/segments/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ),
            'mautic_api_getleadowners'     => array(
                'path'       => '/contacts/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ),
            'mautic_api_getleadfields'     => array(
                'path'       => '/contacts/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ),
            'mautic_api_getleadlists'      => array(
                'path'       => '/contacts/list/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_getlists'          => array(
                'path'       => '/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_listaddlead'       => array(
                'path'       => '/segments/{id}/contact/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ),
            'mautic_api_listremovelead'    => array(
                'path'       => '/segments/{id}/contact/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            ),

            // @deprecated to be removed in 2.0; left here for hard coded URLs/API libraries
            'mautic_api_getleads_bc'          => array(
                'path'       => '/leads',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ),
            'mautic_api_newlead_bc'           => array(
                'path'       => '/leads/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ),
            'mautic_api_getlead_bc'           => array(
                'path'       => '/leads/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ),
            'mautic_api_editputlead_bc'       => array(
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ),
            'mautic_api_editpatchlead_bc'     => array(
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ),
            'mautic_api_deletelead_bc'        => array(
                'path'       => '/leads/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ),
            'mautic_api_getleadsnotes_bc'     => array(
                'path'       => '/leads/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ),
            'mautic_api_getleadscampaigns_bc' => array(
                'path'       => '/leads/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ),
            'mautic_api_getleadslists_bc'     => array(
                'path'       => '/leads/{id}/lists',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ),
            'mautic_api_getleadowners_bc'     => array(
                'path'       => '/leads/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ),
            'mautic_api_getleadfields_bc'     => array(
                'path'       => '/leads/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ),
            'mautic_api_getleadlists_bc'      => array(
                'path'       => '/leads/list/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_getlists_bc'          => array(
                'path'       => '/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_listaddlead_bc'       => array(
                'path'       => '/lists/{id}/lead/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ),
            'mautic_api_listremovelead_bc'    => array(
                'path'       => '/lists/{id}/lead/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            )
        )
    ),
    'menu'     => array(
        'main' => array(
            'items'    => array(
                'mautic.lead.leads' => array(
                    'iconClass' => 'fa-user',
                    'access'    => array('lead:leads:viewown', 'lead:leads:viewother'),
                    'route' => 'mautic_lead_index',
                    'priority' => 80
                ),
                'mautic.lead.list.menu.index'  => array(
                    'iconClass' => 'fa-pie-chart',
                    'access'    => array('lead:leads:viewown', 'lead:leads:viewother'),
                    'route' => 'mautic_leadlist_index',
                    'priority' => 70
                )
            )
        ),
        'admin' => array(
            'priority' => 50,
            'items'    => array(
                'mautic.lead.field.menu.index' => array(
                    'id'        => 'mautic_lead_field',
                    'iconClass' => 'fa-list',
                    'route'  => 'mautic_leadfield_index',
                    'access' => 'lead:fields:full'
                )
            )
        )
    ),
    'services' => array(
        'events'  => array(
            'mautic.lead.subscriber'                => array(
                'class' => 'Mautic\LeadBundle\EventListener\LeadSubscriber'
            ),
            'mautic.lead.emailbundle.subscriber'    => array(
                'class' => 'Mautic\LeadBundle\EventListener\EmailSubscriber'
            ),
            'mautic.lead.formbundle.subscriber'     => array(
                'class' => 'Mautic\LeadBundle\EventListener\FormSubscriber'
            ),
            'mautic.lead.campaignbundle.subscriber' => array(
                'class' => 'Mautic\LeadBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.lead.reportbundle.subscriber'   => array(
                'class' => 'Mautic\LeadBundle\EventListener\ReportSubscriber'
            ),
            'mautic.lead.doctrine.subscriber'       => array(
                'class' => 'Mautic\LeadBundle\EventListener\DoctrineSubscriber',
                'tag'   => 'doctrine.event_subscriber'
            ),
            'mautic.lead.calendarbundle.subscriber' => array(
                'class' => 'Mautic\LeadBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.lead.pointbundle.subscriber'    => array(
                'class' => 'Mautic\LeadBundle\EventListener\PointSubscriber'
            ),
            'mautic.lead.search.subscriber'         => array(
                'class' => 'Mautic\LeadBundle\EventListener\SearchSubscriber'
            ),
            'mautic.webhook.subscriber'             => array(
                'class' => 'Mautic\LeadBundle\EventListener\WebhookSubscriber'
            ),
            'mautic.lead.dashboard.subscriber'      => array(
                'class' => 'Mautic\LeadBundle\EventListener\DashboardSubscriber'
            ),
        ),
        'forms'   => array(
            'mautic.form.type.lead'                           => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead'
            ),
            'mautic.form.type.leadlist'                       => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\ListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist'
            ),
            'mautic.form.type.leadlist_choices'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist_choices'
            ),
            'mautic.form.type.leadlist_filter'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\FilterType',
                'alias'     => 'leadlist_filter',
                'arguments' => 'mautic.factory',
            ),
            'mautic.form.type.leadfield'                      => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\FieldType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadfield'
            ),
            'mautic.form.type.leadlist'                       => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\ListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist'
            ),
            'mautic.form.type.lead.submitaction.pointschange' => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\FormSubmitActionPointsChangeType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_submitaction_pointschange'
            ),
            'mautic.form.type.lead.submitaction.changelist'   => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\EventListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist_action_type'
            ),
            'mautic.form.type.leadpoints_trigger'             => array(
                'class' => 'Mautic\LeadBundle\Form\Type\PointTriggerType',
                'alias' => 'leadpoints_trigger'
            ),
            'mautic.form.type.leadpoints_action'              => array(
                'class' => 'Mautic\LeadBundle\Form\Type\PointActionType',
                'alias' => 'leadpoints_action'
            ),
            'mautic.form.type.leadlist_trigger'               => array(
                'class' => 'Mautic\LeadBundle\Form\Type\ListTriggerType',
                'alias' => 'leadlist_trigger'
            ),
            'mautic.form.type.leadlist_action'                => array(
                'class' => 'Mautic\LeadBundle\Form\Type\ListActionType',
                'alias' => 'leadlist_action'
            ),
            'mautic.form.type.updatelead_action'              => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\UpdateLeadActionType',
                'arguments' => 'mautic.factory',
                'alias'     => 'updatelead_action'
            ),
            'mautic.form.type.leadnote'                       => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\NoteType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadnote'
            ),
            'mautic.form.type.lead_import'                    => array(
                'class' => 'Mautic\LeadBundle\Form\Type\LeadImportType',
                'alias' => 'lead_import'
            ),
            'mautic.form.type.lead_field_import'              => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadImportFieldType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_field_import'
            ),
            'mautic.form.type.lead_quickemail'                => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\EmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_quickemail'
            ),
            'mautic.form.type.lead_tags'                      => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\TagListType',
                'alias'     => 'lead_tags',
                'arguments' => 'mautic.factory'
            ),
            'mautic.form.type.lead_tag'                       => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\TagType',
                'alias'     => 'lead_tag',
                'arguments' => 'mautic.factory'
            ),
            'mautic.form.type.modify_lead_tags'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\ModifyLeadTagsType',
                'alias'     => 'modify_lead_tags',
                'arguments' => 'mautic.factory'
            ),
            'mautic.form.type.lead_batch'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\BatchType',
                'alias'     => 'lead_batch'
            ),
            'mautic.form.type.lead_batch_dnc'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\DncType',
                'alias'     => 'lead_batch_dnc'
            ),
            'mautic.form.type.lead_merge'               => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\MergeType',
                'alias'     => 'lead_merge'
            ),
            'mautic.form.type.campaignevent_lead_field_value'  => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadFieldValueType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignevent_lead_field_value'
            ),
            'mautic.form.type.lead_fields'  => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadFieldsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadfields_choices'
            ),
            'mautic.form.type.lead_dashboard_leads_in_time_widget'  => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\DashboardLeadsInTimeWidgetType',
                'alias'     => 'lead_dashboard_leads_in_time_widget'
            )
        ),
        'other'   => array(
            'mautic.validator.leadlistaccess' => array(
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access'
            ),
            'mautic.lead.constraint.alias'    => array(
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'uniqueleadlist'
            )
        ),
        'helpers' => array(
            'mautic.helper.template.avatar' => array(
                'class'     => 'Mautic\LeadBundle\Templating\Helper\AvatarHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_avatar'
            )
        )
    )
);
