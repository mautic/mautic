<?php

declare(strict_types=1);

namespace Mautic\LeadBundle;

/**
 * Events available for LeadBundle.
 */
final class LeadEvents
{
    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\LeadPostSaveEvent.
     */
    public const string LEAD_POST_SAVE = 'mautic.lead_post_save';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\PointsChangeEvent.
     */
    public const string LEAD_POINTS_CHANGE = 'mautic.lead_points_change';

    /**
     * The mautic.lead_points_change event is dispatched if a lead's points changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\PointsChangeEvent instance.
     */
    public const string LEAD_UTMTAGS_ADD = 'mautic.lead_utmtags_add';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\LeadChangeCompanyEvent.
     */
    public const string LEAD_COMPANY_CHANGE = 'mautic.lead_company_change';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\ListChangeEvent.
     */
    public const string LEAD_LIST_CHANGE = 'mautic.lead_list_change';

    /**
     * The mautic.lead_category_change event is dispatched if a lead's subscribed categories change.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CategoryChangeEvent instance.
     */
    public const string LEAD_CATEGORY_CHANGE = 'mautic.lead_category_change';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\LeadPostDeleteEvent.
     */
    public const string LEAD_POST_DELETE = 'mautic.lead_post_delete';

    /**
     * The mautic.lead_channel_subscription_changed event is dispatched when a lead's DNC status changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ChannelSubscriptionChange instance.
     */
    public const string CHANNEL_SUBSCRIPTION_CHANGED = 'mautic.lead_channel_subscription_changed';

    /**
     * The mautic.lead_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadBuildSearchEvent instance.
     */
    public const string LEAD_BUILD_SEARCH_COMMANDS = 'mautic.lead_build_search_commands';

    /**
     * The mautic.company_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CompanyBuildSearchEvent instance.
     */
    public const string COMPANY_BUILD_SEARCH_COMMANDS = 'mautic.company_build_search_commands';

    /**
     * The mautic.lead_timeline_on_generate event is dispatched when generating a lead's timeline view.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadTimelineEvent instance.
     */
    public const string TIMELINE_ON_GENERATE = 'mautic.lead_timeline_on_generate';

    /**
     * The mautic.lead_import_on_initialize event is dispatched when the import is being initialized.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportInitEvent instance.
     */
    public const string IMPORT_ON_INITIALIZE = 'mautic.lead_import_on_initialize';

    /**
     * The mautic.lead_import_on_field_mapping event is dispatched when the import needs the list of fields for mapping.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportMappingEvent instance.
     */
    public const string IMPORT_ON_FIELD_MAPPING = 'mautic.lead_import_on_field_mapping';

    /**
     * The mautic.lead_import_on_process event is dispatched when the import batch is processing.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     */
    public const string IMPORT_ON_PROCESS = 'mautic.lead_import_on_process';

    /**
     * The mautic.lead_import_on_validate event is dispatched when the import form is being validated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance
     */
    public const string IMPORT_ON_VALIDATE = 'mautic.lead_import_on_validate';

    /**
     * The mautic.filter_choice_fields event is dispatched when the list filter dropdown is populated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\FilterChoiceEvent
     */
    public const string FILTER_CHOICE_FIELDS = 'mautic.filter_choice_fields';

    /**
     * The mautic.lead.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.lead.on_campaign_batch_action';

    /**
     * The mautic.lead.on_campaign_action_delete_contact event is dispatched when the campaign action to delete a contact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_ACTION_DELETE_CONTACT = 'mautic.lead.on_campaign_action_delete_contact';

    /**
     * The mautic.lead.on_campaign_action_add_donotcontact event is dispatched when the campaign action to add a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT = 'mautic.lead.on_campaign_action_add_donotcontact';

    /**
     * The mautic.lead.on_campaign_action_remove_donotcontact event is dispatched when the campaign action to remove a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT = 'mautic.lead.on_campaign_action_remove_donotcontact';

    /**
     * The mautic.lead.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.lead.on_campaign_trigger_condition';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\CompanyPostSaveEvent.
     */
    public const string COMPANY_POST_SAVE = 'mautic.company_post_save';

    /**
     * Webhook event type identifier, the event itself is dispatched as
     * Mautic\LeadBundle\Event\CompanyPostDeleteEvent.
     */
    public const string COMPANY_POST_DELETE = 'mautic.company_post_delete';

    /**
     * The mautic.list_filters_operators_on_generate event is dispatched when the operators for list filters are generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent instance.
     */
    public const string LIST_FILTERS_OPERATORS_ON_GENERATE = 'mautic.list_filters_operators_on_generate';

    /**
     * The mautic.collect_filter_choices_for_list_field_type event is dispatched when some filter based on a list type needs to load its choices.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ListFieldChoicesEvent
     */
    public const string COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE = 'mautic.collect_filter_choices_for_list_field_type';

    /**
     * The mautic.collect_operators_for_field_type event is dispatched when some filter needs operators for a field type.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TypeOperatorsEvent
     */
    public const string COLLECT_OPERATORS_FOR_FIELD_TYPE = 'mautic.collect_operators_for_field_type';

    /**
     * The mautic.collect_operators_for_field event is dispatched when some filter needs operators for a specific field.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TypeOperatorsEvent
     */
    public const string COLLECT_OPERATORS_FOR_FIELD = 'mautic.collect_operators_for_field';

    /**
     * The mautic.adjust_filter_form_type_for_field event is dispatched when the segment filter form is built so events can add new or modify existing fields.
     *
     * The event listener receives a
     * Symfony\Component\Form\FormEvent
     */
    public const string ADJUST_FILTER_FORM_TYPE_FOR_FIELD = 'mautic.adjust_filter_form_type_for_field';

    /**
     * The mautic.list_filters_delegate_decorator event id dispatched when decorator is delegated for segment filter.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent instance.
     */
    public const string SEGMENT_ON_DECORATOR_DELEGATE = 'mautic.list_filters_delegate_decorator';

    /**
     * The mautic.list_filters_merge event is dispatched when the lists rebuilding.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListMergeFiltersEvent instance.
     */
    public const string LIST_FILTERS_MERGE = 'mautic.list_filters_merge';

    /**
     * The mautic.list_filters_querybuilder_generated event is dispatched when the queryBuilder for segment was generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListQueryBuilderGeneratedEvent instance.
     */
    public const string LIST_FILTERS_QUERYBUILDER_GENERATED = 'mautic.list_filters_querybuilder_generated';

    /**
     * The mautic.list_filters_operator_querybuilder_on_generate event is dispatched when the queryBuilder for segment filter operators is being generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent instance.
     */
    public const string LIST_FILTERS_OPERATOR_QUERYBUILDER_ON_GENERATE = 'mautic.list_filters_operator_querybuilder_on_generate';

    /**
     * The mautic.lead_field_pre_add_column event is dispatched before adding a new column to lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnEvent instance.
     */
    public const string LEAD_FIELD_PRE_ADD_COLUMN = 'mautic.lead_field_pre_add_column';

    /**
     * The mautic.lead_field_pre_add_column_background_job event is dispatched before adding a new column to lead_fields table
     * in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent instance.
     */
    public const string LEAD_FIELD_PRE_ADD_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_add_column_background_job';

    /**
     * The mautic.lead_field_pre_update_column event is dispatched before pdating a column in the lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\UpdateColumnEvent instance.
     */
    public const string LEAD_FIELD_PRE_UPDATE_COLUMN = 'mautic.lead_field_pre_update_column';

    /**
     * The mautic.lead_field_pre_update_column_background_job event is dispatched before updating a column in the lead_fields table.
     * in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\UpdateColumnBackgroundEvent instance.
     */
    public const string LEAD_FIELD_PRE_UPDATE_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_update_column_background_job';

    /**
     * The mautic.lead_field_pre_delete_column event is dispatched before deleting a column in the lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\DeleteColumnEvent instance.
     */
    public const string LEAD_FIELD_PRE_DELETE_COLUMN = 'mautic.lead_field_pre_delete_column';

    /**
     * The mautic.lead_field_pre_delete_column_background_job event is dispatched before deleting a column in the
     * lead_fields table in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\DeleteColumnBackgroundEvent instance.
     */
    public const string LEAD_FIELD_PRE_DELETE_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_delete_column_background_job';

    /**
     * The mautic.lead_on_segments_change event is thrown to change lead's segments.
     *
     * The event listener receives a Mautic\FormBundle\Event\SubmissionEvent instance.
     */
    public const string LEAD_ON_SEGMENTS_CHANGE = 'mautic.lead_on_segments_change';
}
