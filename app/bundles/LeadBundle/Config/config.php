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
            'mautic_lead_emailtoken_index' => array(
                'path'       => '/leads/emailtokens/{page}',
                'controller' => 'MauticLeadBundle:SubscribedEvents\EmailToken:index'
            ),
            'mautic_leadlist_index'        => array(
                'path'       => '/leads/lists/{page}',
                'controller' => 'MauticLeadBundle:List:index'
            ),
            'mautic_leadlist_action'       => array(
                'path'       => '/leads/lists/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:List:execute'
            ),
            'mautic_leadfield_index'       => array(
                'path'       => '/leads/fields/{page}',
                'controller' => 'MauticLeadBundle:Field:index'
            ),
            'mautic_leadfield_action'      => array(
                'path'       => '/leads/fields/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Field:execute'
            ),
            'mautic_lead_index'            => array(
                'path'       => '/leads/{page}',
                'controller' => 'MauticLeadBundle:Lead:index'
            ),
            'mautic_leadnote_index'        => array(
                'path'         => '/leads/notes/{leadId}/{page}',
                'controller'   => 'MauticLeadBundle:Note:index',
                'defaults'     => array(
                    'leadId' => 0
                ),
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_leadnote_action'       => array(
                'path'         => '/leads/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'MauticLeadBundle:Note:executeNote',
                'requirements' => array(
                    'leadId' => '\d+'
                )
            ),
            'mautic_lead_action'           => array(
                'path'       => '/leads/{objectAction}/{objectId}',
                'controller' => 'MauticLeadBundle:Lead:execute'
            )
        ),
        'api'  => array(
            'mautic_api_getleads'      => array(
                'path'       => '/leads',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntities'
            ),
            'mautic_api_newlead'       => array(
                'path'       => '/leads/new',
                'controller' => 'MauticLeadBundle:Api\LeadApi:newEntity',
                'method'     => 'POST'
            ),
            'mautic_api_getlead'       => array(
                'path'       => '/leads/{id}',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getEntity'
            ),
            'mautic_api_editputlead'   => array(
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PUT'
            ),
            'mautic_api_editpatchlead' => array(
                'path'       => '/leads/{id}/edit',
                'controller' => 'MauticLeadBundle:Api\LeadApi:editEntity',
                'method'     => 'PATCH'
            ),
            'mautic_api_deletelead'    => array(
                'path'       => '/leads/{id}/delete',
                'controller' => 'MauticLeadBundle:Api\LeadApi:deleteEntity',
                'method'     => 'DELETE'
            ),
            'mautic_api_getleadsnotes'  => array(
                'path'       => '/leads/{id}/notes',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getNotes'
            ),
            'mautic_api_getleadscampaigns'  => array(
                'path'       => '/leads/{id}/campaigns',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getCampaigns'
            ),
            'mautic_api_getleadslists'  => array(
                'path'       => '/leads/{id}/lists',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getLists'
            ),
            'mautic_api_getleadowners' => array(
                'path'       => '/leads/list/owners',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getOwners'
            ),
            'mautic_api_getleadfields' => array(
                'path'       => '/leads/list/fields',
                'controller' => 'MauticLeadBundle:Api\LeadApi:getFields'
            ),
            'mautic_api_getleadlists'  => array(
                'path'       => '/leads/list/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_getlists'  => array(
                'path'       => '/lists',
                'controller' => 'MauticLeadBundle:Api\ListApi:getLists'
            ),
            'mautic_api_listaddlead' => array(
                'path'       => '/lists/{id}/lead/add/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:addLead',
                'method'     => 'POST'
            ),
            'mautic_api_listremovelead' => array(
                'path'       => '/lists/{id}/lead/remove/{leadId}',
                'controller' => 'MauticLeadBundle:Api\ListApi:removeLead',
                'method'     => 'POST'
            )

        )
    ),

    'menu'     => array(
        'main' => array(
            'priority' => 3,
            'items'    => array(
                'mautic.lead.leads' => array(
                    'id'        => 'menu_lead_parent',
                    'iconClass' => 'fa-user',
                    'access'    => array('lead:leads:viewown', 'lead:leads:viewother'),
                    'children'  => array(
                        'mautic.lead.lead.menu.index'  => array(
                            'route' => 'mautic_lead_index',
                        ),
                        'mautic.lead.list.menu.index'  => array(
                            'route' => 'mautic_leadlist_index',
                        ),
                        'mautic.lead.field.menu.index' => array(
                            'route'  => 'mautic_leadfield_index',
                            'access' => 'lead:fields:full'
                        )
                    )
                )
            )
        )
    ),

    'services' => array(
        'events' => array(
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
                'class'     => 'Mautic\LeadBundle\EventListener\DoctrineSubscriber',
                'tag'       => 'doctrine.event_subscriber'
            ),
            'mautic.lead.calendarbundle.subscriber' => array(
                'class' => 'Mautic\LeadBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.lead.pointbundle.subscriber'    => array(
                'class' => 'Mautic\LeadBundle\EventListener\PointSubscriber'
            ),
            'mautic.lead.search.subscriber'         => array(
                'class' => 'Mautic\LeadBundle\EventListener\SearchSubscriber'
            )
        ),
        'forms'  => array(
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
            'mautic.form.type.leadlist_filters'               => array(
                'class' => 'Mautic\LeadBundle\Form\Type\FilterType',
                'alias' => 'leadlist_filters'
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
            'mautic.form.type.lead.submitaction.createlead'   => array(
                'class' => 'Mautic\LeadBundle\Form\Type\FormSubmitActionCreateLeadType',
                'alias' => 'lead_submitaction_createlead'
            ),
            'mautic.form.type.lead.submitaction.mappedfields' => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\FormSubmitActionMappedFieldsType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_submitaction_mappedfields'
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
            'mautic.form.type.lead_field_import'                    => array(
                'class'     => 'Mautic\LeadBundle\Form\Type\LeadImportFieldType',
                'arguments' => 'mautic.factory',
                'alias'     => 'lead_field_import'
            )
        ),
        'other'  => array(
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
        )
    )
);