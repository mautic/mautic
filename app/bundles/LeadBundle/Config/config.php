<?php

declare(strict_types=1);

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
            'mautic_company_graph'     => [
                'path'       => '/company/graph/{objectId}',
                'controller' => 'Mautic\LeadBundle\Controller\CompanyController::graphAction',
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
                'controller'      => Mautic\LeadBundle\Controller\Api\LeadApiController::class,
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
                'controller'      => Mautic\LeadBundle\Controller\Api\ListApiController::class,
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
                'controller'      => Mautic\LeadBundle\Controller\Api\CompanyApiController::class,
            ],
            'mautic_api_companybatchaddcontacts' => [
                'path'       => '/companies/batch/addcontacts',
                'controller' => 'Mautic\LeadBundle\Controller\Api\CompanyApiController::batchAddContactsAction',
                'method'     => 'POST',
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
                'controller'      => Mautic\LeadBundle\Controller\Api\FieldApiController::class,
                'defaults'        => [
                    'object' => 'contact',
                ],
            ],
            'mautic_api_notesstandard' => [
                'standard_entity' => true,
                'name'            => 'notes',
                'path'            => '/notes',
                'controller'      => Mautic\LeadBundle\Controller\Api\NoteApiController::class,
            ],
            'mautic_api_devicesstandard' => [
                'standard_entity' => true,
                'name'            => 'devices',
                'path'            => '/devices',
                'controller'      => Mautic\LeadBundle\Controller\Api\DeviceApiController::class,
            ],
            'mautic_api_tagsstandard' => [
                'standard_entity' => true,
                'name'            => 'tags',
                'path'            => '/tags',
                'controller'      => Mautic\LeadBundle\Controller\Api\TagApiController::class,
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.lead.leads' => [
                    'iconClass' => 'ri-user-6-fill',
                    'access'    => ['lead:leads:viewown', 'lead:leads:viewother'],
                    'route'     => 'mautic_contact_index',
                    'priority'  => 80,
                ],
                'mautic.companies.menu.index' => [
                    'route'     => 'mautic_company_index',
                    'iconClass' => 'ri-building-2-fill',
                    'access'    => ['lead:leads:viewother'],
                    'priority'  => 75,
                ],
                'mautic.lead.list.menu.index' => [
                    'iconClass' => 'ri-pie-chart-fill',
                    'access'    => ['lead:lists:viewown', 'lead:lists:viewother'],
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
                    'iconClass' => 'ri-input-field',
                    'route'     => 'mautic_contactfield_index',
                    'access'    => 'lead:fields:full',
                    'priority'  => 19,
                ],
            ],
        ],
    ],
    'categories' => [
        'segment' => [
            'class' => Mautic\LeadBundle\Entity\LeadList::class,
        ],
    ],
    'parameters' => [
        'parallel_import_limit'               => 1,
        'background_import_if_more_rows_than' => 0,
        'contact_api_count_cache_ttl'         => 5, // in seconds, set null to disable.
        'delete_segment_in_background'        => false,
        'segment_api_count_cache_ttl'         => 43200, // 12 hours in seconds
        'contact_columns'                     => [
            '0' => 'name',
            '1' => 'email',
            '2' => 'location',
            '3' => 'stage',
            '4' => 'points',
            '5' => 'last_active',
            '6' => 'id',
        ],
        'company_columns'                     => [
            '0' => 'companyname',
            '1' => 'companyemail',
            '2' => 'companywebsite',
            '3' => 'score',
            '4' => 'leadcount',
            '5' => 'id',
        ],
        Mautic\LeadBundle\Field\Settings\BackgroundSettings::CREATE_CUSTOM_FIELD_IN_BACKGROUND  => false,
        'company_unique_identifiers_operator'                                                   => Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR,
        'contact_unique_identifiers_operator'                                                   => Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR,
        'segment_rebuild_time_warning'                                                          => 30,
        'segment_build_time_warning'                                                            => 30,
        'contact_export_in_background'                                                          => true,
        'contact_export_notify_admins'                                                          => true,
        'contact_export_dir'                                                                    => '%mautic.application_dir%/media/files/temp',
        'contact_export_batch_size'                                                             => 20000,
        'contact_export_limit'                                                                  => 0,
        'contact_allow_multiple_companies'                                                      => true,
        'import_leads_dir'                                                                      => '%kernel.project_dir%/var/import',
        'update_segment_contact_count_in_background'                                            => false,
        'clear_export_files_after_days'                                                         => 7,
        'update_company_mapping_data_in_background'                                             => false,
    ],
];
