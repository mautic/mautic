<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle;

/**
 * Class LeadEvents
 * Events available for LeadBundle.
 */
final class LeadEvents
{
    /**
     * The mautic.lead_pre_save event is dispatched right before a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_PRE_SAVE = 'mautic.lead_pre_save';

    /**
     * The mautic.lead_post_save event is dispatched right after a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_POST_SAVE = 'mautic.lead_post_save';

    /**
     * The mautic.lead_points_change event is dispatched if a lead's points changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\PointsChangeEvent instance.
     *
     * @var string
     */
    const LEAD_POINTS_CHANGE = 'mautic.lead_points_change';

    /**
     * The mautic.lead_points_change event is dispatched if a lead's points changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\PointsChangeEvent instance.
     *
     * @var string
     */
    const LEAD_UTMTAGS_ADD = 'mautic.lead_utmtags_add';

    /**
     * The mautic.lead_company_change event is dispatched if a lead's company changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadChangeCompanyEvent instance.
     *
     * @var string
     */
    const LEAD_COMPANY_CHANGE = 'mautic.lead_company_change';

    /**
     * The mautic.lead_list_change event is dispatched if a lead's lists changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ListChangeEvent instance.
     *
     * @var string
     */
    const LEAD_LIST_CHANGE = 'mautic.lead_list_change';

    /**
     * The mautic.lead_category_change event is dispatched if a lead's subscribed categories change.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadCategoryEvent instance.
     *
     * @var string
     */
    const LEAD_CATEGORY_CHANGE = 'mautic.lead_category_change';

    /**
     * The mautic.lead_list_batch_change event is dispatched if a batch of leads are changed from ListModel::rebuildListLeads().
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListChange instance.
     *
     * @var string
     */
    const LEAD_LIST_BATCH_CHANGE = 'mautic.lead_list_batch_change';

    /**
     * The mautic.lead_pre_delete event is dispatched before a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_PRE_DELETE = 'mautic.lead_pre_delete';

    /**
     * The mautic.lead_post_delete event is dispatched after a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_POST_DELETE = 'mautic.lead_post_delete';

    /**
     * The mautic.lead_pre_merge event is dispatched before two leads are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadMergeEvent instance.
     *
     * @var string
     */
    const LEAD_PRE_MERGE = 'mautic.lead_pre_merge';

    /**
     * The mautic.lead_post_merge event is dispatched after two leads are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadMergeEvent instance.
     *
     * @var string
     */
    const LEAD_POST_MERGE = 'mautic.lead_post_merge';

    /**
     * The mautic.lead_identified event is dispatched when a lead first becomes known, i.e. name, email, company.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_IDENTIFIED = 'mautic.lead_identified';

    /**
     * The mautic.lead_channel_subscription_changed event is dispatched when a lead's DNC status changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ChannelSubscriptionChange instance.
     *
     * @var string
     */
    const CHANNEL_SUBSCRIPTION_CHANGED = 'mautic.lead_channel_subscription_changed';

    /**
     * The mautic.lead_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadBuildSearchEvent instance.
     *
     * @var string
     */
    const LEAD_BUILD_SEARCH_COMMANDS = 'mautic.lead_build_search_commands';

    /**
     * The mautic.company_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CompanyBuildSearchEvent instance.
     *
     * @var string
     */
    const COMPANY_BUILD_SEARCH_COMMANDS = 'mautic.company_build_search_commands';

    /**
     * The mautic.current_lead_changed event is dispatched when the current lead is changed to another such as when
     * a new lead is created from a form submit.  This gives opportunity to update session data if applicable.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadChangeEvent instance.
     *
     * @var string
     */
    const CURRENT_LEAD_CHANGED = 'mautic.current_lead_changed';

    /**
     * The mautic.lead_list_pre_save event is dispatched right before a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LIST_PRE_SAVE = 'mautic.lead_list_pre_save';

    /**
     * The mautic.lead_list_post_save event is dispatched right after a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_POST_SAVE = 'mautic.lead_list_post_save';

    /**
     * The mautic.lead_list_pre_delete event is dispatched before a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_PRE_DELETE = 'mautic.lead_list_pre_delete';

    /**
     * The mautic.lead_list_post_delete event is dispatched after a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_POST_DELETE = 'mautic.lead_list_post_delete';

    /**
     * The mautic.lead_field_pre_save event is dispatched right before a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_SAVE = 'mautic.lead_field_pre_save';

    /**
     * The mautic.lead_field_post_save event is dispatched right after a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_SAVE = 'mautic.lead_field_post_save';

    /**
     * The mautic.lead_field_pre_delete event is dispatched before a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_DELETE = 'mautic.lead_field_pre_delete';

    /**
     * The mautic.lead_field_post_delete event is dispatched after a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_DELETE = 'mautic.lead_field_post_delete';

    /**
     * The mautic.lead_timeline_on_generate event is dispatched when generating a lead's timeline view.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadTimelineEvent instance.
     *
     * @var string
     */
    const TIMELINE_ON_GENERATE = 'mautic.lead_timeline_on_generate';

    /**
     * The mautic.lead_note_pre_save event is dispatched right before a lead note is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const NOTE_PRE_SAVE = 'mautic.lead_note_pre_save';

    /**
     * The mautic.lead_note_post_save event is dispatched right after a lead note is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const NOTE_POST_SAVE = 'mautic.lead_note_post_save';

    /**
     * The mautic.lead_note_pre_delete event is dispatched before a lead note is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const NOTE_PRE_DELETE = 'mautic.lead_note_pre_delete';

    /**
     * The mautic.lead_note_post_delete event is dispatched after a lead note is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const NOTE_POST_DELETE = 'mautic.lead_note_post_delete';

    /**
     * The mautic.lead_import_pre_save event is dispatched right before an import is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    const IMPORT_PRE_SAVE = 'mautic.lead_import_pre_save';

    /**
     * The mautic.lead_import_post_save event is dispatched right after an import is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    const IMPORT_POST_SAVE = 'mautic.lead_import_post_save';

    /**
     * The mautic.lead_import_pre_delete event is dispatched before an import is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    const IMPORT_PRE_DELETE = 'mautic.lead_import_pre_delete';

    /**
     * The mautic.lead_import_post_delete event is dispatched after an import is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    const IMPORT_POST_DELETE = 'mautic.lead_import_post_delete';

    /**
     * The mautic.lead_import_batch_processed event is dispatched after an import batch is processed.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    const IMPORT_BATCH_PROCESSED = 'mautic.lead_import_batch_processed';

    /**
     * The mautic.lead_device_pre_save event is dispatched right before a lead device is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const DEVICE_PRE_SAVE = 'mautic.lead_device_pre_save';

    /**
     * The mautic.lead_device_post_save event is dispatched right after a lead device is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const DEVICE_POST_SAVE = 'mautic.lead_device_post_save';

    /**
     * The mautic.lead_device_pre_delete event is dispatched before a lead device is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const DEVICE_PRE_DELETE = 'mautic.lead_device_pre_delete';

    /**
     * The mautic.lead_device_post_delete event is dispatched after a lead device is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const DEVICE_POST_DELETE = 'mautic.lead_device_post_delete';

    /**
     * The mautic.lead_tag_pre_save event is dispatched right before a lead tag is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    const TAG_PRE_SAVE = 'mautic.lead_tag_pre_save';

    /**
     * The mautic.lead_tag_post_save event is dispatched right after a lead tag is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    const TAG_POST_SAVE = 'mautic.lead_tag_post_save';

    /**
     * The mautic.lead_tag_pre_delete event is dispatched before a lead tag is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    const TAG_PRE_DELETE = 'mautic.lead_tag_pre_delete';

    /**
     * The mautic.lead_tag_post_delete event is dispatched after a lead tag is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    const TAG_POST_DELETE = 'mautic.lead_tag_post_delete';

    /**
     * The mautic.filter_choice_fields event is dispatched when the list filter dropdown is populated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\FilterChoiceEvent
     *
     * @var string
     */
    const FILTER_CHOICE_FIELDS = 'mautic.filter_choice_fields';

    /**
     * The mautic.lead.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.lead.on_campaign_trigger_action';

    /**
     * The mautic.lead.on_campaign_action_delete_contact event is dispatched when the campaign action to delete a contact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_ACTION_DELETE_CONTACT = 'mautic.lead.on_campaign_action_delete_contact';

    /**
     * The mautic.lead.on_campaign_action_add_donotcontact event is dispatched when the campaign action to add a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT = 'mautic.lead.on_campaign_action_add_donotcontact';

    /**
     * The mautic.lead.on_campaign_action_remove_donotcontact event is dispatched when the campaign action to remove a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT = 'mautic.lead.on_campaign_action_remove_donotcontact';

    /**
     * The mautic.lead.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.lead.on_campaign_trigger_condition';

    /**
     * The mautic.company_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_PRE_SAVE = 'mautic.company_pre_save';

    /**
     * The mautic.company_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_POST_SAVE = 'mautic.company_post_save';

    /**
     * The mautic.company_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_PRE_DELETE = 'mautic.company_pre_delete';

    /**
     * The mautic.company_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_POST_DELETE = 'mautic.company_post_delete';

    /**
     * The mautic.list_filters_choices_on_generate event is dispatched when the choices for list filters are generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent instance.
     *
     * @var string
     */
    const LIST_FILTERS_CHOICES_ON_GENERATE = 'mautic.list_filters_choices_on_generate';

    /**
     * The mautic.list_filters_operators_on_generate event is dispatched when the operators for list filters are generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent instance.
     *
     * @var string
     */
    const LIST_FILTERS_OPERATORS_ON_GENERATE = 'mautic.list_filters_operators_on_generate';

    /**
     * The mautic.list_filters_on_filtering event is dispatched when the lists are updated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFilteringEvent instance.
     *
     * @var string
     */
    const LIST_FILTERS_ON_FILTERING = 'mautic.list_filters_on_filtering';

    /**
     * The mautic.list_filters_querybuilder_generated event is dispatched when the queryBuilder for segment was generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListQueryBuilderGeneratedEvent instance.
     *
     * @var string
     */
    const LIST_FILTERS_QUERYBUILDER_GENERATED = 'mautic.list_filters_querybuilder_generated';

    /**
     * The mautic.list_filters_on_filtering event is dispatched when the lists are updated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFilteringEvent instance.
     *
     * @var string
     */
    const LIST_PRE_PROCESS_LIST = 'mautic.list_pre_process_list';

    /**
     * The mautic.clickthrough_contact_identification event is dispatched when a clickthrough array is parsed from a tracking
     * URL.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactIdentificationEvent instance.
     *
     * @var string
     */
    const ON_CLICKTHROUGH_IDENTIFICATION = 'mautic.clickthrough_contact_identification';

    /**
     * The mautic.lead_field_pre_add_column event is dispatched before adding a new column to lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnEvent instance.
     *
     * @var string
     */
    const LEAD_FIELD_PRE_ADD_COLUMN = 'mautic.lead_field_pre_add_column';

    /**
     * The mautic.lead_field_pre_add_column_background_job event is dispatched before adding a new column to lead_fields table
     * in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent instance.
     *
     * @var string
     */
    const LEAD_FIELD_PRE_ADD_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_add_column_background_job';
}
