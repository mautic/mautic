<?php

declare(strict_types=1);

use Mautic\FormBundle\Helper\BlockedFreeEmailProvidersHelper;

return [
    'routes' => [
        'api' => [
            'mautic_api_formstandard' => [
                'standard_entity' => true,
                'name'            => 'forms',
                'path'            => '/forms',
                'controller'      => Mautic\FormBundle\Controller\Api\FormApiController::class,
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'items' => [
                'mautic.form.forms' => [
                    'route'    => 'mautic_form_index',
                    'access'   => ['form:forms:viewown', 'form:forms:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 200,
                ],
            ],
        ],
    ],

    'categories' => [
        'form' => [
            'class' => Mautic\FormBundle\Entity\Form::class,
        ],
    ],

    'parameters' => [
        'form_upload_dir'              => '%mautic.application_dir%/media/files/form',
        'blacklisted_extensions'       => ['php', 'sh'],
        'do_not_submit_emails'         => [],
        'blocked_free_email_providers' => BlockedFreeEmailProvidersHelper::load(),
        'form_results_data_sources'    => false,
        'successful_submit_action'     => 'top',
        'form_field_autofill'          => false,
    ],
];
