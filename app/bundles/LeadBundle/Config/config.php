<?php

declare(strict_types=1);

return [
    'routes' => [
        'api' => [
            'mautic_api_contactsstandard' => [
                'standard_entity' => true,
                'name'            => 'contacts',
                'path'            => '/contacts',
                'controller'      => Mautic\LeadBundle\Controller\Api\LeadApiController::class,
            ],
            'mautic_api_segmentsstandard' => [
                'standard_entity' => true,
                'name'            => 'lists',
                'path'            => '/segments',
                'controller'      => Mautic\LeadBundle\Controller\Api\ListApiController::class,
            ],
            'mautic_api_companiesstandard' => [
                'standard_entity' => true,
                'name'            => 'companies',
                'path'            => '/companies',
                'controller'      => Mautic\LeadBundle\Controller\Api\CompanyApiController::class,
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
