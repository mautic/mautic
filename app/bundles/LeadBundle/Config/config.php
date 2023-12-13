<?php

return [
    'routes' => [
        'main' => [
            'mautic_plugin_timeline_index' => [
                'path'         => '/plugin/{integration}/timeline/{page}',
                'controller'   => 'Mautic\LeadBundle\Controller\TimelineController::pluginIndexAction',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_plugin_timeline_view' => [
                'path'         => '/plugin/{integration}/timeline/view/{leadId}/{page}',
                'controller'   => 'Mautic\LeadBundle\Controller\TimelineController::pluginViewAction',
                'requirements' => [
                    'integration' => '.+',
                    'leadId'      => '\d+',
                ],
            ],
            'mautic_segment_batch_contact_set' => [
                'path'       => '/segments/batch/contact/set',
                'controller' => 'Mautic\LeadBundle\Controller\BatchSegmentController::setAction',
            ],
            'mautic_segment_batch_contact_view' => [
                'path'       => '/segments/batch/contact/view',
                'controller' => 'Mautic\LeadBundle\Controller\BatchSegmentController::indexAction',
            ],
            'mautic_segment_index' => [
                'path'       => '/segments/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\ListController::indexAction',
            ],
            'mautic_segment_action' => [
                'path'       => '/segments/{objectAction}/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\ListController::executeAction',
            ],
            'mautic_contactfield_index' => [
                'path'       => '/contacts/fields/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
            ],
            'mautic_contactfield_action' => [
                'path'       => '/contacts/fields/{objectAction}/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\FieldController::executeAction',
            ],
            'mautic_contact_index' => [
                'path'       => '/contacts/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\LeadController::indexAction',
            ],
            'mautic_contactnote_index' => [
                'path'       => '/contacts/notes/{leadId}/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\NoteController::indexAction',
                'defaults'   => [
                    'leadId' => 0,
                ],
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contactnote_action' => [
                'path'         => '/contacts/notes/{leadId}/{objectAction}/{objectId}',
                'controller'   => 'Mautic\LeadBundle\Controller\NoteController::executeNoteAction',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contacttimeline_action' => [
                'path'         => '/contacts/timeline/{leadId}/{page}',
                'controller'   => 'Mautic\LeadBundle\Controller\TimelineController::indexAction',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_timeline_export_action' => [
                'path'         => '/contacts/timeline/batchExport/{leadId}',
                'controller'   => 'Mautic\LeadBundle\Controller\TimelineController::batchExportAction',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_auditlog_action' => [
                'path'         => '/contacts/auditlog/{leadId}/{page}',
                'controller'   => 'Mautic\LeadBundle\Controller\AuditlogController::indexAction',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_auditlog_export_action' => [
                'path'         => '/contacts/auditlog/batchExport/{leadId}',
                'controller'   => 'Mautic\LeadBundle\Controller\AuditlogController::batchExportAction',
                'requirements' => [
                    'leadId' => '\d+',
                ],
            ],
            'mautic_contact_export_action' => [
                'path'         => '/contacts/contact/export/{contactId}',
                'controller'   => 'Mautic\LeadBundle\Controller\LeadController::contactExportAction',
                'requirements' => [
                    'contactId' => '\d+',
                ],
            ],
            'mautic_import_index' => [
                'path'       => '/{object}/import/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\ImportController::indexAction',
            ],
            'mautic_import_action' => [
                'path'       => '/{object}/import/{objectAction}/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\ImportController::executeAction',
            ],
            'mautic_contact_action' => [
                'path'       => '/contacts/{objectAction}/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\LeadController::executeAction',
            ],
            'mautic_company_index' => [
                'path'       => '/companies/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\CompanyController::indexAction',
            ],
            'mautic_company_contacts_list' => [
                'path'         => '/company/{objectId}/contacts/{page}',
                'controller'   => 'Mautic\LeadBundle\Controller\CompanyController::contactsListAction',
                'requirements' => [
                    'objectId' => '\d+',
                ],
            ],
            'mautic_company_action' => [
                'path'       => '/companies/{objectAction}/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\CompanyController::executeAction',
            ],
            'mautic_company_export_action' => [
                'path'         => '/companies/company/export/{companyId}',
                'controller'   => 'Mautic\LeadBundle\Controller\CompanyController::companyExportAction',
                'requirements' => [
                    'companyId' => '\d+',
                ],
            ],
            'mautic_segment_contacts' => [
                'path'       => '/segment/view/{objectId}/contact/{page}',
                'controller' => 'Mautic\LeadBundle\Controller\ListController::contactsAction',
            ],
            'mautic_contact_stats' => [
                'path'       => '/contacts/view/{objectId}/stats',
                'controller' => 'Mautic\LeadBundle\Controller\LeadController::contactStatsAction',
            ],
            'mautic_contact_export_download' => [
                'path'       => '/contacts/export/download/{fileName}',
                'controller' => 'Mautic\LeadBundle\Controller\LeadController::downloadExportAction',
            ],
        ],
        'api' => [
            'mautic_api_contactsstandard' => [
                'standard_entity' => true,
                'name'            => 'contacts',
                'path'            => '/contacts',
                'controller'      => \Mautic\LeadBundle\Controller\Api\LeadApiController::class,
            ],
            'mautic_api_dncaddcontact' => [
                'path'       => '/contacts/{id}/dnc/{channel}/add',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::addDncAction',
                'method'     => 'POST',
                'defaults'   => [
                    'channel' => 'email',
                ],
            ],
            'mautic_api_dncremovecontact' => [
                'path'       => '/contacts/{id}/dnc/{channel}/remove',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::removeDncAction',
                'method'     => 'POST',
            ],
            'mautic_api_getcontactevents' => [
                'path'       => '/contacts/{id}/activity',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getActivityAction',
            ],
            'mautic_api_getcontactsevents' => [
                'path'       => '/contacts/activity',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getAllActivityAction',
            ],
            'mautic_api_getcontactnotes' => [
                'path'       => '/contacts/{id}/notes',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getNotesAction',
            ],
            'mautic_api_getcontactdevices' => [
                'path'       => '/contacts/{id}/devices',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getDevicesAction',
            ],
            'mautic_api_getcontactcampaigns' => [
                'path'       => '/contacts/{id}/campaigns',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getCampaignsAction',
            ],
            'mautic_api_getcontactssegments' => [
                'path'       => '/contacts/{id}/segments',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getListsAction',
            ],
            'mautic_api_getcontactscompanies' => [
                'path'       => '/contacts/{id}/companies',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getCompaniesAction',
            ],
            'mautic_api_utmcreateevent' => [
                'path'       => '/contacts/{id}/utm/add',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::addUtmTagsAction',
                'method'     => 'POST',
            ],
            'mautic_api_utmremoveevent' => [
                'path'       => '/contacts/{id}/utm/{utmid}/remove',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::removeUtmTagsAction',
                'method'     => 'POST',
            ],
            'mautic_api_getcontactowners' => [
                'path'       => '/contacts/list/owners',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getOwnersAction',
            ],
            'mautic_api_getcontactfields' => [
                'path'       => '/contacts/list/fields',
                'controller' => 'Mautic\LeadBundle\Controller\Api\LeadApiController::getFieldsAction',
            ],
            'mautic_api_getcontactsegments' => [
                'path'       => '/contacts/list/segments',
                'controller' => 'Mautic\LeadBundle\Controller\Api\ListApiController::getListsAction',
            ],
            'mautic_api_segmentsstandard' => [
                'standard_entity' => true,
                'name'            => 'lists',
                'path'            => '/segments',
                'controller'      => \Mautic\LeadBundle\Controller\Api\ListApiController::class,
            ],
            'mautic_api_segmentaddcontact' => [
                'path'       => '/segments/{id}/contact/{leadId}/add',
                'controller' => 'Mautic\LeadBundle\Controller\Api\ListApiController::addLeadAction',
                'method'     => 'POST',
            ],
            'mautic_api_segmentaddcontacts' => [
                'path'       => '/segments/{id}/contacts/add',
                'controller' => 'Mautic\LeadBundle\Controller\Api\ListApiController::addLeadsAction',
                'method'     => 'POST',
            ],
            'mautic_api_segmentremovecontact' => [
                'path'       => '/segments/{id}/contact/{leadId}/remove',
                'controller' => 'Mautic\LeadBundle\Controller\Api\ListApiController::removeLeadAction',
                'method'     => 'POST',
            ],
            'mautic_api_companiesstandard' => [
                'standard_entity' => true,
                'name'            => 'companies',
                'path'            => '/companies',
                'controller'      => \Mautic\LeadBundle\Controller\Api\CompanyApiController::class,
            ],
            'mautic_api_companyaddcontact' => [
                'path'       => '/companies/{companyId}/contact/{contactId}/add',
                'controller' => 'Mautic\LeadBundle\Controller\Api\CompanyApiController::addContactAction',
                'method'     => 'POST',
            ],
            'mautic_api_companyremovecontact' => [
                'path'       => '/companies/{companyId}/contact/{contactId}/remove',
                'controller' => 'Mautic\LeadBundle\Controller\Api\CompanyApiController::removeContactAction',
                'method'     => 'POST',
            ],
            'mautic_api_fieldsstandard' => [
                'standard_entity' => true,
                'name'            => 'fields',
                'path'            => '/fields/{object}',
                'controller'      => \Mautic\LeadBundle\Controller\Api\FieldApiController::class,
                'defaults'        => [
                    'object' => 'contact',
                ],
            ],
            'mautic_api_notesstandard' => [
                'standard_entity' => true,
                'name'            => 'notes',
                'path'            => '/notes',
                'controller'      => \Mautic\LeadBundle\Controller\Api\NoteApiController::class,
            ],
            'mautic_api_devicesstandard' => [
                'standard_entity' => true,
                'name'            => 'devices',
                'path'            => '/devices',
                'controller'      => \Mautic\LeadBundle\Controller\Api\DeviceApiController::class,
            ],
            'mautic_api_tagsstandard' => [
                'standard_entity' => true,
                'name'            => 'tags',
                'path'            => '/tags',
                'controller'      => \Mautic\LeadBundle\Controller\Api\TagApiController::class,
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.lead.leads' => [
                    'iconClass' => 'fa-user',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route'     => 'mautic_contact_index',
                    'priority'  => 80,
                ],
                'mautic.companies.menu.index' => [
                    'route'     => 'mautic_company_index',
                    'iconClass' => 'fa-building-o',
                    'access'    => ['lead:leads:viewother'],
                    'priority'  => 75,
                ],
                'mautic.lead.list.menu.index' => [
                    'iconClass' => 'fa-pie-chart',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route'     => 'mautic_segment_index',
                    'priority'  => 70,
                ],
            ],
        ],
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.lead.field.menu.index' => [
                    'id'        => 'mautic_lead_field',
                    'iconClass' => 'fa-list',
                    'route'     => 'mautic_contactfield_index',
                    'access'    => 'lead:fields:full',
                ],
            ],
        ],
    ],
    'categories' => [
        'segment' => null,
    ],
    'services' => [
        'events' => [
            'mautic.lead.serializer.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\SerializerSubscriber::class,
                'arguments' => [
                    'request_stack',
                ],
                'tag'          => 'jms_serializer.event_subscriber',
                'tagArguments' => [
                    'event' => \JMS\Serializer\EventDispatcher\Events::POST_SERIALIZE,
                ],
            ],
            'mautic.lead.export_scheduled_audit_log_subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ContactExportSchedulerAuditLogSubscriber::class,
                'arguments' => [
                    'mautic.core.model.auditlog',
                    'mautic.helper.ip_lookup',
                ],
            ],
            'mautic.lead.export_scheduled_logger_subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ContactExportSchedulerLoggerSubscriber::class,
                'arguments' => [
                    'logger',
                ],
            ],
            'mautic.lead.export_scheduled_notification_subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ContactExportSchedulerNotificationSubscriber::class,
                'arguments' => [
                    'mautic.core.model.notification',
                    'translator',
                ],
            ],
            'mautic.lead.contact_scheduled_export.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\ContactScheduledExportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.export_scheduler',
                ],
            ],
        ],
        'other' => [
            'mautic.lead.doctrine.subscriber' => [
                'class'     => \Mautic\LeadBundle\EventListener\DoctrineSubscriber::class,
                'tag'       => 'doctrine.event_subscriber',
                'arguments' => ['monolog.logger.mautic'],
            ],
            'mautic.validator.leadlistaccess' => [
                'class'     => \Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator::class,
                'arguments' => ['mautic.lead.model.list'],
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access',
            ],
            'mautic.validator.emailaddress' => [
                'class'     => \Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator::class,
                'arguments' => [
                    'mautic.validator.email',
                ],
                'tag'       => 'validator.constraint_validator',
            ],
            \Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class => [
                'class'     => \Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class,
                'tag'       => 'validator.constraint_validator',
                'arguments' => [
                    'mautic.lead.model.list',
                    'mautic.helper.field.alias',
                    '@doctrine.orm.entity_manager',
                    'translator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            \Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class => [
                'class'     => \Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class,
                'tag'       => 'validator.constraint_validator',
                'arguments' => [
                    'mautic.lead.model.list',
                    'mautic.helper.field.alias',
                ],
            ],
            'mautic.lead.constraint.alias' => [
                'class'     => \Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator::class,
                'arguments' => ['mautic.lead.repository.lead_list', 'mautic.helper.user'],
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'uniqueleadlist',
            ],
            'mautic.lead.validator.custom_field' => [
                'class'     => \Mautic\LeadBundle\Validator\CustomFieldValidator::class,
                'arguments' => ['mautic.lead.model.field', 'translator'],
            ],
            'mautic.lead_list.constraint.in_use' => [
                'class'     => Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUseValidator::class,
                'arguments' => [
                    'mautic.lead.model.list',
                ],
                'tag'   => 'validator.constraint_validator',
                'alias' => 'segment_in_use',
            ],
            'mautic.lead.event.dispatcher' => [
                'class'     => \Mautic\LeadBundle\Helper\LeadChangeEventDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.merger' => [
                'class'     => \Mautic\LeadBundle\Deduplicate\ContactMerger::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.merged_records',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.deduper' => [
                'class'     => \Mautic\LeadBundle\Deduplicate\ContactDeduper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.merger',
                    'mautic.lead.repository.lead',
                ],
            ],
            'mautic.company.deduper' => [
                'class'     => \Mautic\LeadBundle\Deduplicate\CompanyDeduper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.repository.company',
                ],
            ],
            'mautic.lead.helper.primary_company' => [
                'class'     => \Mautic\LeadBundle\Helper\PrimaryCompanyHelper::class,
                'arguments' => [
                    'mautic.lead.repository.company_lead',
                ],
            ],
            'mautic.lead.helper.contact_request_helper' => [
                'class'     => \Mautic\LeadBundle\Helper\ContactRequestHelper::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.tracker.contact',
                    'mautic.helper.core_parameters',
                    'mautic.helper.ip_lookup',
                    'request_stack',
                    'monolog.logger.mautic',
                    'event_dispatcher',
                    'mautic.lead.merger',
                ],
            ],
            'mautic.lead.validator.length' => [
                'class' => Mautic\LeadBundle\Validator\Constraints\LengthValidator::class,
                'tag'   => 'validator.constraint_validator',
            ],
            'mautic.lead.segment.stat.dependencies' => [
                'class'     => \Mautic\LeadBundle\Segment\Stat\SegmentDependencies::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'mautic.campaign.model.campaign',
                    'mautic.form.model.action',
                    'mautic.lead.model.list',
                    'mautic.point.model.triggerevent',
                    'mautic.report.model.report',
                ],
            ],
            'mautic.lead.segment.stat.chart.query.factory' => [
                'class'     => \Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory::class,
                'arguments' => [
                ],
            ],
            'mautic.lead.segment.stat.campaign.share' => [
                'class'     => \Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare::class,
                'arguments' => [
                    'mautic.campaign.model.campaign',
                    'mautic.helper.cache_storage',
                    '@doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.columns.dictionary' => [
                'class'     => \Mautic\LeadBundle\Services\ContactColumnsDictionary::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'translator',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.lead.model.lead_segment_filter_factory' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentFilterFactory::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_schema_cache',
                    '@service_container',
                    'mautic.lead.model.lead_segment_decorator_factory',
                ],
            ],
            'mautic.tracker.contact' => [
                'class'     => \Mautic\LeadBundle\Tracker\ContactTracker::class,
                'arguments' => [
                    'mautic.lead.repository.lead',
                    'mautic.lead.service.contact_tracking_service',
                    'mautic.tracker.device',
                    'mautic.security',
                    'monolog.logger.mautic',
                    'mautic.helper.ip_lookup',
                    'request_stack',
                    'mautic.helper.core_parameters',
                    'event_dispatcher',
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.tracker.device' => [
                'class'     => \Mautic\LeadBundle\Tracker\DeviceTracker::class,
                'arguments' => [
                    'mautic.lead.service.device_creator_service',
                    'mautic.lead.factory.device_detector_factory',
                    'mautic.lead.service.device_tracking_service',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.field.custom_field_column' => [
                'class'     => Mautic\LeadBundle\Field\CustomFieldColumn::class,
                'arguments' => [
                    'mautic.schema.helper.column',
                    'mautic.lead.field.schema_definition',
                    'monolog.logger.mautic',
                    'mautic.lead.field.lead_field_saver',
                    'mautic.lead.field.custom_field_index',
                    'mautic.lead.field.dispatcher.field_column_dispatcher',
                    'translator',
                ],
            ],
            'mautic.lead.field.custom_field_index' => [
                'class'     => Mautic\LeadBundle\Field\CustomFieldIndex::class,
                'arguments' => [
                    'mautic.schema.helper.index',
                    'monolog.logger.mautic',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
            'mautic.lead.repository.lead_segment_filter_descriptor' => [
                'class'     => \Mautic\LeadBundle\Services\ContactSegmentFilterDictionary::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.service.segment_dependency_tree_factory' => [
                'class'     => \Mautic\LeadBundle\Services\SegmentDependencyTreeFactory::class,
                'arguments' => [
                    'mautic.lead.model.list',
                    'router',
                ],
            ],
            'mautic.lead.repository.lead_segment_query_builder' => [
                'class'     => Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.random_parameter_name',
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.model.lead_segment_service' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentService::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_factory',
                    'mautic.lead.repository.lead_segment_query_builder',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.lead.model.lead_segment_schema_cache' => [
                'class'     => \Mautic\LeadBundle\Segment\TableSchemaColumnsCache::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.model.relative_date' => [
                'class'     => \Mautic\LeadBundle\Segment\RelativeDate::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.lead.model.lead_segment_filter_operator' => [
                'class'     => \Mautic\LeadBundle\Segment\ContactSegmentFilterOperator::class,
                'arguments' => [
                    'mautic.lead.provider.fillterOperator',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_factory' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class,
                'arguments' => [
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                    'mautic.lead.model.lead_segment_decorator_base',
                    'mautic.lead.model.lead_segment_decorator_custom_mapped',
                    'mautic.lead.model.lead_segment.decorator.date.optionFactory',
                    'mautic.lead.model.lead_segment_decorator_company',
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_base' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_custom_mapped' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_company' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment_decorator_date' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\DateDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
            'mautic.lead.model.lead_segment.decorator.date.optionFactory' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_decorator_date',
                    'mautic.lead.model.relative_date',
                    'mautic.lead.model.lead_segment.timezoneResolver',
                ],
            ],
            'mautic.lead.model.lead_segment.timezoneResolver' => [
                'class'     => \Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.lead.provider.fillterOperator' => [
                'class'     => \Mautic\LeadBundle\Provider\FilterOperatorProvider::class,
                'arguments' => [
                    'event_dispatcher',
                    'translator',
                ],
            ],
            'mautic.lead.provider.typeOperator' => [
                'class'     => \Mautic\LeadBundle\Provider\TypeOperatorProvider::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.lead.provider.fillterOperator',
                ],
            ],
            'mautic.lead.provider.fieldChoices' => [
                'class'     => \Mautic\LeadBundle\Provider\FieldChoicesProvider::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.provider.formAdjustments' => [
                'class'     => \Mautic\LeadBundle\Provider\FormAdjustmentsProvider::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.model.random_parameter_name' => [
                'class' => \Mautic\LeadBundle\Segment\RandomParameterName::class,
            ],
            'mautic.lead.segment.operator_options' => [
                'class' => \Mautic\LeadBundle\Segment\OperatorOptions::class,
            ],
            'mautic.lead.reportbundle.fields_builder' => [
                'class'     => \Mautic\LeadBundle\Report\FieldsBuilder::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.model.list',
                    'mautic.user.model.user',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.lead.factory.device_detector_factory' => [
                'class'     => \Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory::class,
                'arguments' => [
                    'mautic.cache.provider',
                ],
            ],
            'mautic.lead.service.contact_tracking_service' => [
                'class'     => \Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingService::class,
                'arguments' => [
                    'mautic.helper.cookie',
                    'mautic.lead.repository.lead_device',
                    'mautic.lead.repository.lead',
                    'mautic.lead.repository.merged_records',
                    'request_stack',
                ],
            ],
            'mautic.lead.service.device_creator_service' => [
                'class' => \Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService::class,
            ],
            'mautic.lead.service.device_tracking_service' => [
                'class'     => \Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService::class,
                'arguments' => [
                    'mautic.helper.cookie',
                    'doctrine.orm.entity_manager',
                    'mautic.lead.repository.lead_device',
                    'mautic.helper.random',
                    'request_stack',
                    'mautic.security',
                ],
            ],

            'mautic.lead.field.schema_definition' => [
                'class' => Mautic\LeadBundle\Field\SchemaDefinition::class,
            ],
            'mautic.lead.field.dispatcher.field_save_dispatcher' => [
                'class'     => Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.lead.field.dispatcher.field_column_dispatcher' => [
                'class'     => Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.lead.field.settings.background_settings',
                ],
            ],
            'mautic.lead.field.dispatcher.field_column_background_dispatcher' => [
                'class'     => Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.field.fields_with_unique_identifier' => [
                'class'     => Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier::class,
                'arguments' => [
                    'mautic.lead.field.field_list',
                ],
            ],
            'mautic.lead.field.field_list' => [
                'class'     => Mautic\LeadBundle\Field\FieldList::class,
                'arguments' => [
                    'mautic.lead.repository.field',
                    'translator',
                ],
            ],
            'mautic.lead.field.identifier_fields' => [
                'class'     => \Mautic\LeadBundle\Field\IdentifierFields::class,
                'arguments' => [
                    'mautic.lead.field.fields_with_unique_identifier',
                    'mautic.lead.field.field_list',
                ],
            ],
            'mautic.lead.field.lead_field_saver' => [
                'class'     => Mautic\LeadBundle\Field\LeadFieldSaver::class,
                'arguments' => [
                    'mautic.lead.repository.field',
                    'mautic.lead.field.dispatcher.field_save_dispatcher',
                ],
            ],
            'mautic.lead.field.settings.background_settings' => [
                'class'     => Mautic\LeadBundle\Field\Settings\BackgroundSettings::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.lead.field.settings.background_service' => [
                'class'     => Mautic\LeadBundle\Field\BackgroundService::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.lead.field.custom_field_column',
                    'mautic.lead.field.lead_field_saver',
                    'mautic.lead.field.dispatcher.field_column_background_dispatcher',
                    'mautic.lead.field.notification.custom_field',
                ],
            ],
            'mautic.lead.field.notification.custom_field' => [
                'class'     => Mautic\LeadBundle\Field\Notification\CustomFieldNotification::class,
                'arguments' => [
                    'mautic.core.model.notification',
                    'mautic.user.model.user',
                    'translator',
                ],
            ],
        ],
        'repositories' => [
            'mautic.lead.repository.company' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\Company::class,
                ],
                'methodCalls' => [
                    'setUniqueIdentifiersOperator' => [
                        '%mautic.company_unique_identifiers_operator%',
                    ],
                ],
            ],
            'mautic.lead.repository.company_lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\CompanyLead::class,
                ],
            ],
            'mautic.lead.repository.stages_lead_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\StagesChangeLog::class,
                ],
            ],
            'mautic.lead.repository.dnc' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\DoNotContact::class,
                ],
            ],
            'mautic.lead.repository.lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\Lead::class,
                ],
                'methodCalls' => [
                    'setUniqueIdentifiersOperator' => [
                        '%mautic.contact_unique_identifiers_operator%',
                    ],
                    'setListLeadRepository' => [
                        '@mautic.lead.repository.list_lead',
                    ],
                ],
            ],
            'mautic.lead.repository.list_lead' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\ListLead::class,
                ],
            ],
            'mautic.lead.repository.frequency_rule' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\FrequencyRule::class,
                ],
            ],
            'mautic.lead.repository.lead_event_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadEventLog::class,
                ],
            ],
            'mautic.lead.repository.lead_device' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadDevice::class,
                ],
            ],
            'mautic.lead.repository.lead_list' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadList::class,
                ],
            ],
            'mautic.lead.repository.points_change_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\PointsChangeLog::class,
                ],
            ],
            'mautic.lead.repository.merged_records' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\MergeRecord::class,
                ],
            ],
            'mautic.lead.repository.field' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\LeadBundle\Entity\LeadField::class,
                ],
            ],
            //  Segment Filter Query builders
            'mautic.lead.query.builder.basic' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.foreign.value' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.foreign.func' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.special.dnc' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.special.integration' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.special.sessions' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.complex_relation.value' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class,
                'arguments' => ['mautic.lead.model.random_parameter_name', 'event_dispatcher'],
            ],
            'mautic.lead.query.builder.special.leadlist' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder::class,
                'arguments' => [
                    'mautic.lead.model.random_parameter_name',
                    'mautic.lead.repository.lead_segment_query_builder',
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead_segment_filter_factory',
                    'event_dispatcher',
                ],
            ],
            'mautic.lead.query.builder.channel_click.value' => [
                'class'     => \Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder::class,
                'arguments' => [
                    'mautic.lead.model.random_parameter_name',
                    'event_dispatcher',
                ],
            ],
        ],
        'helpers' => [
            'mautic.helper.twig.avatar' => [
                'class'     => Mautic\LeadBundle\Twig\Helper\AvatarHelper::class,
                'arguments' => [
                    'twig.helper.assets',
                    'mautic.helper.paths',
                    'mautic.helper.twig.gravatar',
                    'mautic.helper.twig.default_avatar',
                ],
                'alias' => 'lead_avatar',
            ],
            'mautic.helper.twig.default_avatar' => [
                'class'     => Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper::class,
                'arguments' => [
                    'twig.helper.assets',
                ],
                'alias' => 'default_avatar',
            ],
            'mautic.helper.field.alias' => [
                'class'     => \Mautic\LeadBundle\Helper\FieldAliasHelper::class,
                'arguments' => ['mautic.lead.model.field'],
            ],
            'mautic.helper.twig.dnc_reason' => [
                'class'     => Mautic\LeadBundle\Twig\Helper\DncReasonHelper::class,
                'arguments' => ['translator'],
                'alias'     => 'lead_dnc_reason',
            ],
            'mautic.helper.segment.count.cache' => [
                'class'     => \Mautic\LeadBundle\Helper\SegmentCountCacheHelper::class,
                'arguments' => ['mautic.helper.cache_storage'],
            ],
        ],
        'fixtures' => [
            'mautic.lead.fixture.company' => [
                'class'     => \Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.lead.model.company'],
            ],
            'mautic.lead.fixture.contact' => [
                'class'     => \Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.helper.core_parameters'],
            ],
            'mautic.lead.fixture.segment' => [
                'class'     => \Mautic\LeadBundle\DataFixtures\ORM\LoadLeadListData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.lead.model.list'],
            ],
            'mautic.lead.fixture.category' => [
                'class'     => \Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
            ],
            'mautic.lead.fixture.categorizedleadlists' => [
                'class'     => \Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
            ],
            'mautic.lead.fixture.test.page_hit' => [
                'class'    => \Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData::class,
                'tag'      => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'optional' => true,
            ],
            'mautic.lead.fixture.test.segment' => [
                'class'     => \Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.lead.model.list', 'mautic.lead.model.lead'],
                'optional'  => true,
            ],
            'mautic.lead.fixture.test.click' => [
                'class'     => \Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.lead.model.list', 'mautic.lead.model.lead'],
                'optional'  => true,
            ],
            'mautic.lead.fixture.test.dnc' => [
                'class'     => \Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.lead.model.list', 'mautic.lead.model.lead'],
                'optional'  => true,
            ],
            'mautic.lead.fixture.test.tag' => [
                'class'     => \Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'optional'  => true,
            ],
        ],
    ],
    'parameters' => [
        'parallel_import_limit'               => 1,
        'background_import_if_more_rows_than' => 0,
        'contact_columns'                     => [
            '0' => 'name',
            '1' => 'email',
            '2' => 'location',
            '3' => 'stage',
            '4' => 'points',
            '5' => 'last_active',
            '6' => 'id',
        ],
        \Mautic\LeadBundle\Field\Settings\BackgroundSettings::CREATE_CUSTOM_FIELD_IN_BACKGROUND => false,
        'company_unique_identifiers_operator'                                                   => \Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR,
        'contact_unique_identifiers_operator'                                                   => \Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR,
        'segment_rebuild_time_warning'                                                          => 30,
        'segment_build_time_warning'                                                            => 30,
        'contact_export_in_background'                                                          => true,
        'contact_export_dir'                                                                    => '%mautic.application_dir%/media/files/temp',
        'contact_export_batch_size'                                                             => 20000,
    ],
];
