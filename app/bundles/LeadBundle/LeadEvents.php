<?php

namespace Mautic\LeadBundle;

/**
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
    public const LEAD_PRE_SAVE = 'mautic.lead_pre_save';

    /**
     * The mautic.lead_post_save event is dispatched right after a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const LEAD_POST_SAVE = 'mautic.lead_post_save';

    /**
     * The mautic.lead_points_change event is dispatched if a lead's points changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\PointsChangeEvent instance.
     *
     * @var string
     */
    public const LEAD_POINTS_CHANGE = 'mautic.lead_points_change';

    /**
     * The mautic.lead_points_change event is dispatched if a lead's points changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\PointsChangeEvent instance.
     *
     * @var string
     */
    public const LEAD_UTMTAGS_ADD = 'mautic.lead_utmtags_add';

    /**
     * The mautic.lead_company_change event is dispatched if a lead's company changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadChangeCompanyEvent instance.
     *
     * @var string
     */
    public const LEAD_COMPANY_CHANGE = 'mautic.lead_company_change';

    /**
     * The mautic.lead_list_change event is dispatched if a lead's lists changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ListChangeEvent instance.
     *
     * @var string
     */
    public const LEAD_LIST_CHANGE = 'mautic.lead_list_change';

    /**
     * The mautic.lead_category_change event is dispatched if a lead's subscribed categories change.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CategoryChangeEvent instance.
     *
     * @var string
     */
    public const LEAD_CATEGORY_CHANGE = 'mautic.lead_category_change';

    /**
     * The mautic.lead_list_batch_change event is dispatched if a batch of leads are changed from ListModel::rebuildListLeads().
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListChange instance.
     *
     * @var string
     */
    public const LEAD_LIST_BATCH_CHANGE = 'mautic.lead_list_batch_change';

    /**
     * The mautic.lead_pre_delete event is dispatched before a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const LEAD_PRE_DELETE = 'mautic.lead_pre_delete';

    /**
     * The mautic.lead_post_delete event is dispatched after a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const LEAD_POST_DELETE = 'mautic.lead_post_delete';

    /**
     * The mautic.lead_pre_merge event is dispatched before two leads are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadMergeEvent instance.
     *
     * @var string
     */
    public const LEAD_PRE_MERGE = 'mautic.lead_pre_merge';

    /**
     * The mautic.lead_post_merge event is dispatched after two leads are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadMergeEvent instance.
     *
     * @var string
     */
    public const LEAD_POST_MERGE = 'mautic.lead_post_merge';

    /**
     * The mautic.lead_identified event is dispatched when a lead first becomes known, i.e. name, email, company.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const LEAD_IDENTIFIED = 'mautic.lead_identified';

    /**
     * The mautic.lead_channel_subscription_changed event is dispatched when a lead's DNC status changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ChannelSubscriptionChange instance.
     *
     * @var string
     */
    public const CHANNEL_SUBSCRIPTION_CHANGED = 'mautic.lead_channel_subscription_changed';

    /**
     * The mautic.lead_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadBuildSearchEvent instance.
     *
     * @var string
     */
    public const LEAD_BUILD_SEARCH_COMMANDS = 'mautic.lead_build_search_commands';

    /**
     * The mautic.company_build_search_commands event is dispatched when the search commands are built.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CompanyBuildSearchEvent instance.
     *
     * @var string
     */
    public const COMPANY_BUILD_SEARCH_COMMANDS = 'mautic.company_build_search_commands';

    /**
     * The mautic.current_lead_changed event is dispatched when the current lead is changed to another such as when
     * a new lead is created from a form submit.  This gives opportunity to update session data if applicable.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadChangeEvent instance.
     *
     * @var string
     */
    public const CURRENT_LEAD_CHANGED = 'mautic.current_lead_changed';

    /**
     * The mautic.lead_list_pre_save event is dispatched right before a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const LIST_PRE_SAVE = 'mautic.lead_list_pre_save';

    /**
     * The mautic.lead_list_post_save event is dispatched right after a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    public const LIST_POST_SAVE = 'mautic.lead_list_post_save';

    /**
     * The mautic.lead_list_pre_unpublish event is dispatched before a lead_list is unpublished.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    public const LIST_PRE_UNPUBLISH = 'mautic.lead_list_pre_unpublish';

    /**
     * The mautic.lead_list_pre_delete event is dispatched before a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    public const LIST_PRE_DELETE = 'mautic.lead_list_pre_delete';

    /**
     * The mautic.lead_list_post_delete event is dispatched after a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    public const LIST_POST_DELETE = 'mautic.lead_list_post_delete';

    /**
     * The mautic.lead_field_pre_save event is dispatched right before a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const FIELD_PRE_SAVE = 'mautic.lead_field_pre_save';

    /**
     * The mautic.lead_field_post_save event is dispatched right after a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const FIELD_POST_SAVE = 'mautic.lead_field_post_save';

    /**
     * The mautic.lead_field_pre_delete event is dispatched before a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const FIELD_PRE_DELETE = 'mautic.lead_field_pre_delete';

    /**
     * The mautic.lead_field_post_delete event is dispatched after a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const FIELD_POST_DELETE = 'mautic.lead_field_post_delete';

    /**
     * The mautic.lead_timeline_on_generate event is dispatched when generating a lead's timeline view.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadTimelineEvent instance.
     *
     * @var string
     */
    public const TIMELINE_ON_GENERATE = 'mautic.lead_timeline_on_generate';

    /**
     * The mautic.lead_note_pre_save event is dispatched right before a lead note is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const NOTE_PRE_SAVE = 'mautic.lead_note_pre_save';

    /**
     * The mautic.lead_note_post_save event is dispatched right after a lead note is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const NOTE_POST_SAVE = 'mautic.lead_note_post_save';

    /**
     * The mautic.lead_note_pre_delete event is dispatched before a lead note is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const NOTE_PRE_DELETE = 'mautic.lead_note_pre_delete';

    /**
     * The mautic.lead_note_post_delete event is dispatched after a lead note is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const NOTE_POST_DELETE = 'mautic.lead_note_post_delete';

    /**
     * The mautic.lead_import_pre_save event is dispatched right before an import is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_PRE_SAVE = 'mautic.lead_import_pre_save';

    /**
     * The mautic.lead_import_post_save event is dispatched right after an import is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_POST_SAVE = 'mautic.lead_import_post_save';

    /**
     * The mautic.lead_import_pre_delete event is dispatched before an import is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_PRE_DELETE = 'mautic.lead_import_pre_delete';

    /**
     * The mautic.lead_import_post_delete event is dispatched after an import is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_POST_DELETE = 'mautic.lead_import_post_delete';

    /**
     * The mautic.lead_import_on_initialize event is dispatched when the import is being initialized.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportInitEvent instance.
     *
     * @var string
     */
    public const IMPORT_ON_INITIALIZE = 'mautic.lead_import_on_initialize';

    /**
     * The mautic.lead_import_on_field_mapping event is dispatched when the import needs the list of fields for mapping.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportMappingEvent instance.
     *
     * @var string
     */
    public const IMPORT_ON_FIELD_MAPPING = 'mautic.lead_import_on_field_mapping';

    /**
     * The mautic.lead_import_on_process event is dispatched when the import batch is processing.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_ON_PROCESS = 'mautic.lead_import_on_process';

    /**
     * The mautic.lead_import_on_validate event is dispatched when the import form is being validated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance
     */
    public const IMPORT_ON_VALIDATE = 'mautic.lead_import_on_validate';

    /**
     * The mautic.lead_import_batch_processed event is dispatched after an import batch is processed.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ImportEvent instance.
     *
     * @var string
     */
    public const IMPORT_BATCH_PROCESSED = 'mautic.lead_import_batch_processed';

    /**
     * The mautic.lead_device_pre_save event is dispatched right before a lead device is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    public const DEVICE_PRE_SAVE = 'mautic.lead_device_pre_save';

    /**
     * The mautic.lead_device_post_save event is dispatched right after a lead device is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const DEVICE_POST_SAVE = 'mautic.lead_device_post_save';

    /**
     * The mautic.lead_device_pre_delete event is dispatched before a lead device is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const DEVICE_PRE_DELETE = 'mautic.lead_device_pre_delete';

    /**
     * The mautic.lead_device_post_delete event is dispatched after a lead device is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    public const DEVICE_POST_DELETE = 'mautic.lead_device_post_delete';

    /**
     * The mautic.lead_tag_pre_save event is dispatched right before a lead tag is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    public const TAG_PRE_SAVE = 'mautic.lead_tag_pre_save';

    /**
     * The mautic.lead_tag_post_save event is dispatched right after a lead tag is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    public const TAG_POST_SAVE = 'mautic.lead_tag_post_save';

    /**
     * The mautic.lead_tag_pre_delete event is dispatched before a lead tag is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    public const TAG_PRE_DELETE = 'mautic.lead_tag_pre_delete';

    /**
     * The mautic.lead_tag_post_delete event is dispatched after a lead tag is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TagEvent instance.
     *
     * @var string
     */
    public const TAG_POST_DELETE = 'mautic.lead_tag_post_delete';

    /**
     * The mautic.filter_choice_fields event is dispatched when the list filter dropdown is populated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\FilterChoiceEvent
     *
     * @var string
     */
    public const FILTER_CHOICE_FIELDS = 'mautic.filter_choice_fields';

    /**
     * @deprecated Listen to ON_CAMPAIGN_BATCH_ACTION instead.
     *
     * The mautic.lead.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.lead.on_campaign_trigger_action';

    /**
     * The mautic.lead.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_BATCH_ACTION = 'mautic.lead.on_campaign_batch_action';

    /**
     * The mautic.lead.on_campaign_action_delete_contact event is dispatched when the campaign action to delete a contact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_ACTION_DELETE_CONTACT = 'mautic.lead.on_campaign_action_delete_contact';

    /**
     * The mautic.lead.on_campaign_action_add_donotcontact event is dispatched when the campaign action to add a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_ACTION_ADD_DONOTCONTACT = 'mautic.lead.on_campaign_action_add_donotcontact';

    /**
     * The mautic.lead.on_campaign_action_remove_donotcontact event is dispatched when the campaign action to remove a donotcontact is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_ACTION_REMOVE_DONOTCONTACT = 'mautic.lead.on_campaign_action_remove_donotcontact';

    /**
     * The mautic.lead.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.lead.on_campaign_trigger_condition';

    /**
     * The mautic.company_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    public const COMPANY_PRE_SAVE = 'mautic.company_pre_save';

    /**
     * The mautic.company_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    public const COMPANY_POST_SAVE = 'mautic.company_post_save';

    /**
     * The mautic.company_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    public const COMPANY_PRE_DELETE = 'mautic.company_pre_delete';

    /**
     * The mautic.company_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\LeadBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    public const COMPANY_POST_DELETE = 'mautic.company_post_delete';

    /**
     * The mautic.company_pre_merge event is dispatched before two companies are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CompanyMergeEvent instance.
     *
     * @var string
     */
    public const COMPANY_PRE_MERGE = 'mautic.company_pre_merge';

    /**
     * The mautic.company_post_merge event is dispatched after two companies are merged.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\CompanyMergeEvent instance.
     *
     * @var string
     */
    public const COMPANY_POST_MERGE = 'mautic.company_post_merge';

    /**
     * The mautic.list_filters_choices_on_generate event is dispatched when the choices for list filters are generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_CHOICES_ON_GENERATE = 'mautic.list_filters_choices_on_generate';

    /**
     * The event is dispatched to allow inserting segment filters translations.
     *
     * The listener receives SegmentDictionaryGenerationEvent
     */
    public const SEGMENT_DICTIONARY_ON_GENERATE = 'mautic.list_dictionary_on_generate';

    /**
     * The mautic.list_filters_operators_on_generate event is dispatched when the operators for list filters are generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_OPERATORS_ON_GENERATE = 'mautic.list_filters_operators_on_generate';

    /**
     * The mautic.collect_filter_choices_for_list_field_type event is dispatched when some filter based on a list type needs to load its choices.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ListFieldChoicesEvent
     *
     * @var string
     */
    public const COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE = 'mautic.collect_filter_choices_for_list_field_type';

    /**
     * The mautic.collect_operators_for_field_type event is dispatched when some filter needs operators for a field type.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TypeOperatorsEvent
     *
     * @var string
     */
    public const COLLECT_OPERATORS_FOR_FIELD_TYPE = 'mautic.collect_operators_for_field_type';

    /**
     * The mautic.collect_operators_for_field event is dispatched when some filter needs operators for a specific field.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\TypeOperatorsEvent
     *
     * @var string
     */
    public const COLLECT_OPERATORS_FOR_FIELD = 'mautic.collect_operators_for_field';

    /**
     * The mautic.adjust_filter_form_type_for_field event is dispatched when the segment filter form is built so events can add new or modify existing fields.
     *
     * The event listener receives a
     * Symfony\Component\Form\FormEvent
     *
     * @var string
     */
    public const ADJUST_FILTER_FORM_TYPE_FOR_FIELD = 'mautic.adjust_filter_form_type_for_field';

    /**
     * The mautic.list_filters_delegate_decorator event id dispatched when decorator is delegated for segment filter.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent instance.
     */
    public const SEGMENT_ON_DECORATOR_DELEGATE = 'mautic.list_filters_delegate_decorator';

    /**
     * The mautic.list_filters_on_filtering event is dispatched when the lists are updated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFilteringEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_ON_FILTERING = 'mautic.list_filters_on_filtering';

    /**
     * The mautic.list_filters_merge event is dispatched when the lists rebuilding.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListMergeFiltersEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_MERGE = 'mautic.list_filters_merge';

    /**
     * The mautic.list_filters_querybuilder_generated event is dispatched when the queryBuilder for segment was generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListQueryBuilderGeneratedEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_QUERYBUILDER_GENERATED = 'mautic.list_filters_querybuilder_generated';

    /**
     * The mautic.list_filters_operator_querybuilder_on_generate event is dispatched when the queryBuilder for segment filter operators is being generated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent instance.
     *
     * @var string
     */
    public const LIST_FILTERS_OPERATOR_QUERYBUILDER_ON_GENERATE = 'mautic.list_filters_operator_querybuilder_on_generate';

    /**
     * The mautic.list_filters_on_filtering event is dispatched when the lists are updated.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListFilteringEvent instance.
     *
     * @var string
     */
    public const LIST_PRE_PROCESS_LIST = 'mautic.list_pre_process_list';

    /**
     * The mautic.clickthrough_contact_identification event is dispatched when a clickthrough array is parsed from a tracking
     * URL.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactIdentificationEvent instance.
     *
     * @var string
     */
    public const ON_CLICKTHROUGH_IDENTIFICATION = 'mautic.clickthrough_contact_identification';

    /**
     * The mautic.lead_field_pre_add_column event is dispatched before adding a new column to lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnEvent instance.
     *
     * @var string
     */
    public const LEAD_FIELD_PRE_ADD_COLUMN = 'mautic.lead_field_pre_add_column';

    /**
     * The mautic.lead_field_pre_add_column_background_job event is dispatched before adding a new column to lead_fields table
     * in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent instance.
     *
     * @var string
     */
    public const LEAD_FIELD_PRE_ADD_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_add_column_background_job';

    /**
     * The mautic.lead_field_pre_update_column event is dispatched before pdating a column in the lead_fields table.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\UpdateColumnEvent instance.
     *
     * @var string
     */
    public const LEAD_FIELD_PRE_UPDATE_COLUMN = 'mautic.lead_field_pre_update_column';

    /**
     * The mautic.lead_field_pre_add_column_background_job event is dispatched before updating a column in the lead_fields table.
     * in background job.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Field\Event\UpdateColumnBackgroundEvent instance.
     *
     * @var string
     */
    public const LEAD_FIELD_PRE_UPDATE_COLUMN_BACKGROUND_JOB = 'mautic.lead_field_pre_update_column_background_job';
    /**
     * The mautic.post_contact_export_scheduled event is dispatched when a contact export is scheduled.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactExportSchedulerEvent instance.
     */
    public const POST_CONTACT_EXPORT_SCHEDULED = 'mautic.post_contact_export_scheduled';

    public const POST_CONTACT_EXPORT = 'mautic.post_contact_export';

    /**
     * The mautic.contact_export_prepare_file event is dispatched when a contact export is being processed.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactExportSchedulerEvent instance.
     */
    public const CONTACT_EXPORT_PREPARE_FILE = 'mautic.contact_export_prepare_file';

    /**
     * The mautic.contact_export_prepare_file event is dispatched when a contact export email is to be sent.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactExportSchedulerEvent instance.
     */
    public const CONTACT_EXPORT_SEND_EMAIL = 'mautic.contact_export_send_email';

    /**
     * The mautic.post_contact_export_send_email event is dispatched when a contact export email is sent.
     *
     * The event listener receives a Mautic\LeadBundle\Event\ContactExportSchedulerEvent instance.
     */
    public const POST_CONTACT_EXPORT_SEND_EMAIL = 'mautic.post_contact_export_send_email';

    /**
     * The mautic.lead_on_segments_change event is thrown to change lead's segments.
     *
     * The event listener receives a Mautic\FormBundle\Event\SubmissionEvent instance.
     *
     * @var string
     */
    public const LEAD_ON_SEGMENTS_CHANGE = 'mautic.lead_on_segments_change';

    /**
     * The mautic.pre_batch_save event is dispatched before a list of entities is being built.
     *
     * The event listener receives a Mautic\LeadBundle\Event\SaveBatchLeadsEvent instance.
     *
     * @var string
     */
    public const LEAD_PRE_BATCH_SAVE = 'mautic.lead_pre_batch_save';

    /**
     * The mautic.ost_batch_save event is dispatched when a list of entities is saved.
     *
     * The event listener receives a Mautic\LeadBundle\Event\SaveBatchLeadsEvent instance.
     *
     * @var string
     */
    public const LEAD_POST_BATCH_SAVE = 'mautic.lead_post_batch_save';
}
