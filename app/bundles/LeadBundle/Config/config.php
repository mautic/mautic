<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes'   => [
        'main' => [
            'mautic_contact_emailtoken_index' => [
                'path'       => '/contacts/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ],
            'mautic_segment_index'        => [
                'path'       => '/segments/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ],
            'mautic_segment_action'       => [
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ],
            'mautic_contactfield_index'       => [
                'path'       => '/contacts/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ],
            'mautic_contactfield_action'      => [
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ],
            'mautic_contact_index'            => [
                'path'       => '/contacts/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ],
            'mautic_contactnote_index'        => [
                'path'         => '/contacts/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => [
                    'leadId' => 0
                ],
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_contactnote_action'       => [
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_contact_action'           => [
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            ],

            // @todo remove in 2.0 - left here till core references are all updated
            'mautic_lead_emailtoken_index' => [
                'path'       => '/contacts/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ],
            'mautic_leadlist_index'        => [
                'path'       => '/segments/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ],
            'mautic_leadlist_action'       => [
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ],
            'mautic_leadfield_index'       => [
                'path'       => '/contacts/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ],
            'mautic_leadfield_action'      => [
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ],
            'mautic_lead_index'            => [
                'path'       => '/contacts/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ],
            'mautic_leadnote_index'        => [
                'path'         => '/contacts/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => [
                    'leadId' => 0
                ],
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_leadnote_action'       => [
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_lead_action'           => [
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            ],

            // @deprecated to be removed in 2.0; left here just in case a URL is hardcoded
            'mautic_lead_emailtoken_index_bc' => [
                'path'       => '/leads/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\BuilderToken:index'
            ],
            'mautic_leadlist_index_bc'        => [
                'path'       => '/leads/lists/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ],
            'mautic_leadlist_action_bc'       => [
                'path'       => '/leads/lists/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ],
            'mautic_leadfield_index_bc'       => [
                'path'       => '/leads/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ],
            'mautic_leadfield_action_bc'      => [
                'path'       => '/leads/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ],
            'mautic_lead_index_bc'            => [
                'path'       => '/leads/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ],
            'mautic_leadnote_index_bc'        => [
                'path'         => '/leads/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => [
                    'leadId' => 0
                ],
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_leadnote_action_bc'       => [
                'path'         => '/leads/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => [
                    'leadId' => '\d+'
                ]
            ],
            'mautic_lead_action_bc'           => [
                'path'       => '/leads/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            ]
        ],
        'api'  => [
            'mautic_api_getcontacts'          => [
                'path'       => '/contacts',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ],
            'mautic_api_newcontact'           => [
                'path'       => '/contacts/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ],
            'mautic_api_getcontact'           => [
                'path'       => '/contacts/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ],
            'mautic_api_editputcontact'       => [
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ],
            'mautic_api_editpatchcontact'     => [
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ],
            'mautic_api_deletecontact'        => [
                'path'       => '/contacts/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ],
            'mautic_api_getcontactnotes'     => [
                'path'       => '/contacts/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ],
            'mautic_api_getcontactcampaigns' => [
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ],
            'mautic_api_getsegments'     => [
                'path'       => '/segments/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ],
            'mautic_api_getcontactowners'     => [
                'path'       => '/contacts/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ],
            'mautic_api_getcontactfields'     => [
                'path'       => '/contacts/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ],
            'mautic_api_getcontactsegments'      => [
                'path'       => '/contacts/list/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_getsegments'          => [
                'path'       => '/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_segmentaddcontact'       => [
                'path'       => '/segments/{id}/contact/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ],
            'mautic_api_segmentremovecontact'    => [
                'path'       => '/segments/{id}/contact/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            ],

            // @deprecated to be removed in 2.0; left here till routes have been renamed
            'mautic_api_getleads'          => [
                'path'       => '/contacts',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ],
            'mautic_api_newlead'           => [
                'path'       => '/contacts/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ],
            'mautic_api_getlead'           => [
                'path'       => '/contacts/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ],
            'mautic_api_editputlead'       => [
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ],
            'mautic_api_editpatchlead'     => [
                'path'       => '/contacts/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ],
            'mautic_api_deletelead'        => [
                'path'       => '/contacts/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ],
            'mautic_api_getleadsnotes'     => [
                'path'       => '/contacts/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ],
            'mautic_api_getleadscampaigns' => [
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ],
            'mautic_api_getleadslists'     => [
                'path'       => '/segments/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ],
            'mautic_api_getleadowners'     => [
                'path'       => '/contacts/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ],
            'mautic_api_getleadfields'     => [
                'path'       => '/contacts/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ],
            'mautic_api_getleadlists'      => [
                'path'       => '/contacts/list/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_getlists'          => [
                'path'       => '/segments',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_listaddlead'       => [
                'path'       => '/segments/{id}/contact/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ],
            'mautic_api_listremovelead'    => [
                'path'       => '/segments/{id}/contact/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            ],

            // @deprecated to be removed in 2.0; left here for hard coded URLs/API libraries
            'mautic_api_getleads_bc'          => [
                'path'       => '/leads',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ],
            'mautic_api_newlead_bc'           => [
                'path'       => '/leads/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ],
            'mautic_api_getlead_bc'           => [
                'path'       => '/leads/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ],
            'mautic_api_editputlead_bc'       => [
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ],
            'mautic_api_editpatchlead_bc'     => [
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ],
            'mautic_api_deletelead_bc'        => [
                'path'       => '/leads/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ],
            'mautic_api_getleadsnotes_bc'     => [
                'path'       => '/leads/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ],
            'mautic_api_getleadscampaigns_bc' => [
                'path'       => '/leads/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ],
            'mautic_api_getleadslists_bc'     => [
                'path'       => '/leads/{id}/lists',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ],
            'mautic_api_getleadowners_bc'     => [
                'path'       => '/leads/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ],
            'mautic_api_getleadfields_bc'     => [
                'path'       => '/leads/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ],
            'mautic_api_getleadlists_bc'      => [
                'path'       => '/leads/list/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_getlists_bc'          => [
                'path'       => '/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ],
            'mautic_api_listaddlead_bc'       => [
                'path'       => '/lists/{id}/lead/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ],
            'mautic_api_listremovelead_bc'    => [
                'path'       => '/lists/{id}/lead/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            ]
        ]
    ],
    'menu'     => [
        'main' => [
            'items'    => [
                'mautic.lead.leads' => [
                    'iconClass' => 'fa-user',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route' => 'mautic_lead_index',
                    'priority' => 80
                ],
                'mautic.lead.list.menu.index'  => [
                    'iconClass' => 'fa-pie-chart',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route' => 'mautic_leadlist_index',
                    'priority' => 70
                ]
            ]
        ],
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.lead.field.menu.index' => [
                    'id'        => 'mautic_lead_field',
                    'iconClass' => 'fa-list',
                    'route'  => 'mautic_leadfield_index',
                    'access' => 'lead:fields:full'
                ]
            ]
        ]
    ],
    'services' => [
        'events'  => [
            'mautic.lead.subscriber'                => [
                'class' => 'Mautic\LeadBundle\EventListener\LeadSubscriber'
            ],
            'mautic.lead.emailbundle.subscriber'    => [
                'class' => 'Mautic\LeadBundle\EventListener\EmailSubscriber'
            ],
            'mautic.lead.formbundle.subscriber'     => [
                'class' => 'Mautic\LeadBundle\EventListener\FormSubscriber'
            ],
            'mautic.lead.campaignbundle.subscriber' => [
                'class' => 'Mautic\LeadBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.factory',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field'
                ]
            ],
            'mautic.lead.reportbundle.subscriber'   => [
                'class' => 'Mautic\LeadBundle\EventListener\ReportSubscriber'
            ],
            'mautic.lead.doctrine.subscriber'       => [
                'class' => 'Mautic\LeadBundle\EventListener\DoctrineSubscriber',
                'tag'   => 'doctrine.event_subscriber'
            ],
            'mautic.lead.calendarbundle.subscriber' => [
                'class' => 'Mautic\LeadBundle\EventListener\CalendarSubscriber'
            ],
            'mautic.lead.pointbundle.subscriber'    => [
                'class' => 'Mautic\LeadBundle\EventListener\PointSubscriber'
            ],
            'mautic.lead.search.subscriber'         => [
                'class' => 'Mautic\LeadBundle\EventListener\SearchSubscriber'
            ],
            'mautic.webhook.subscriber'             => [
                'class' => 'Mautic\LeadBundle\EventListener\WebhookSubscriber'
            ],
            'mautic.lead.dashboard.subscriber'      => [
                'class' => 'Mautic\LeadBundle\EventListener\DashboardSubscriber'
            ],
        ],
        'forms'   => [
            'mautic.form.type.lead'                           => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead'
            ],
            'mautic.form.type.leadlist'                       => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist'
            ],
            'mautic.form.type.leadlist_choices'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist_choices'
            ],
            'mautic.form.type.leadlist_filter'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\FilterType',
                'alias'     => 'leadlist_filter',
                'arguments' => 'mautic.factory',
            ],
            'mautic.form.type.leadfield'                      => [
                'class'     => 'Mautic\LeadBundle\Form\Type\FieldType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadfield'
            ],
            'mautic.form.type.lead.submitaction.pointschange' => [
                'class'     => 'Mautic\LeadBundle\Form\Type\FormSubmitActionPointsChangeType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_submitaction_pointschange'
            ],
            'mautic.form.type.lead.submitaction.changelist'   => [
                'class'     => 'Mautic\LeadBundle\Form\Type\EventListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadlist_action_type'
            ],
            'mautic.form.type.leadpoints_trigger'             => [
                'class' => 'Mautic\LeadBundle\Form\Type\PointTriggerType',
                'alias' => 'leadpoints_trigger'
            ],
            'mautic.form.type.leadpoints_action'              => [
                'class' => 'Mautic\LeadBundle\Form\Type\PointActionType',
                'alias' => 'leadpoints_action'
            ],
            'mautic.form.type.leadlist_trigger'               => [
                'class' => 'Mautic\LeadBundle\Form\Type\ListTriggerType',
                'alias' => 'leadlist_trigger'
            ],
            'mautic.form.type.leadlist_action'                => [
                'class' => 'Mautic\LeadBundle\Form\Type\ListActionType',
                'alias' => 'leadlist_action'
            ],
            'mautic.form.type.updatelead_action'              => [
                'class'     => 'Mautic\LeadBundle\Form\Type\UpdateLeadActionType',
                'arguments' => 'mautic.factory',
                'alias'     => 'updatelead_action'
            ],
            'mautic.form.type.leadnote'                       => [
                'class'     => 'Mautic\LeadBundle\Form\Type\NoteType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadnote'
            ],
            'mautic.form.type.lead_import'                    => [
                'class' => 'Mautic\LeadBundle\Form\Type\LeadImportType',
                'alias' => 'lead_import'
            ],
            'mautic.form.type.lead_field_import'              => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadImportFieldType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_field_import'
            ],
            'mautic.form.type.lead_quickemail'                => [
                'class'     => 'Mautic\LeadBundle\Form\Type\EmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_quickemail'
            ],
            'mautic.form.type.lead_tags'                      => [
                'class'     => 'Mautic\LeadBundle\Form\Type\TagListType',
                'alias'     => 'lead_tags',
                'arguments' => 'mautic.factory'
            ],
            'mautic.form.type.lead_tag'                       => [
                'class'     => 'Mautic\LeadBundle\Form\Type\TagType',
                'alias'     => 'lead_tag',
                'arguments' => 'mautic.factory'
            ],
            'mautic.form.type.modify_lead_tags'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\ModifyLeadTagsType',
                'alias'     => 'modify_lead_tags',
                'arguments' => 'mautic.factory'
            ],
            'mautic.form.type.lead_batch'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\BatchType',
                'alias'     => 'lead_batch'
            ],
            'mautic.form.type.lead_batch_dnc'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\DncType',
                'alias'     => 'lead_batch_dnc'
            ],
            'mautic.form.type.lead_merge'               => [
                'class'     => 'Mautic\LeadBundle\Form\Type\MergeType',
                'alias'     => 'lead_merge'
            ],
            'mautic.form.type.campaignevent_lead_field_value'  => [
                'class'     => 'Mautic\LeadBundle\Form\Type\CampaignEventLeadFieldValueType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignevent_lead_field_value'
            ],
            'mautic.form.type.lead_fields'  => [
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadFieldsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'leadfields_choices'
            ],
            'mautic.form.type.lead_dashboard_leads_in_time_widget'  => [
                'class'     => 'Mautic\LeadBundle\Form\Type\DashboardLeadsInTimeWidgetType',
                'alias'     => 'lead_dashboard_leads_in_time_widget'
            ]
        ],
        'other'   => [
            'mautic.validator.leadlistaccess' => [
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access'
            ],
            'mautic.lead.constraint.alias'    => [
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'uniqueleadlist'
            ]
        ],
        'helpers' => [
            'mautic.helper.template.avatar' => [
                'class'     => 'Mautic\LeadBundle\Templating\Helper\AvatarHelper',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_avatar'
            ]
        ],
        'models' =>  [
            'mautic.lead.model.lead' => [
                'class' => 'Mautic\LeadBundle\Model\LeadModel',
                'arguments' => [
                    'request_stack',
                    'mautic.helper.cookie',
                    'mautic.helper.ip_lookup',
                    'mautic.helper.paths',
                    'mautic.helper.integration',
                    'mautic.lead.model.field',
                    'mautic.lead.model.list'
                ]
            ],
            'mautic.lead.model.field' => [
                'class' => 'Mautic\LeadBundle\Model\FieldModel',
                'arguments' => [
                    'mautic.schema.helper.factory'
                ]
            ],
            'mautic.lead.model.list' => [
                'class' => 'Mautic\LeadBundle\Model\ListModel',
                'arguments' => [
                    'mautic.helper.core_parameters'
                ]
            ],
            'mautic.lead.model.note' => [
                'class' => 'Mautic\LeadBundle\Model\NoteModel'
            ]
        ]
    ]
];
