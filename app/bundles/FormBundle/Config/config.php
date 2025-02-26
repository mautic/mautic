<?php

use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\Form\Type\SubmitActionEmailType;
use Mautic\FormBundle\Form\Type\SubmitActionRepostType;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Helper\TokenHelper;
use Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator;
use Mautic\FormBundle\Validator\UploadFieldValidator;

return [
    'routes' => [
        'main' => [
            'mautic_formaction_action' => [
                'path'       => '/forms/action/{objectAction}/{objectId}',
                'controller' => 'Mautic\FormBundle\Controller\ActionController::executeAction',
            ],
            'mautic_formfield_action' => [
                'path'       => '/forms/field/{objectAction}/{objectId}',
                'controller' => 'Mautic\FormBundle\Controller\FieldController::executeAction',
            ],
            'mautic_form_index' => [
                'path'       => '/forms/{page}',
                'controller' => 'Mautic\FormBundle\Controller\FormController::indexAction',
            ],
            'mautic_form_results' => [
                'path'       => '/forms/results/{objectId}/{page}',
                'controller' => 'Mautic\FormBundle\Controller\ResultController::indexAction',
            ],
            'mautic_form_export' => [
                'path'       => '/forms/results/{objectId}/export/{format}',
                'controller' => 'Mautic\FormBundle\Controller\ResultController::exportAction',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_form_results_action' => [
                'path'       => '/forms/results/{formId}/{objectAction}/{objectId}',
                'controller' => 'Mautic\FormBundle\Controller\ResultController::executeAction',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
            'mautic_form_action' => [
                'path'       => '/forms/{objectAction}/{objectId}',
                'controller' => 'Mautic\FormBundle\Controller\FormController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_formstandard' => [
                'standard_entity' => true,
                'name'            => 'forms',
                'path'            => '/forms',
                'controller'      => Mautic\FormBundle\Controller\Api\FormApiController::class,
            ],
            'mautic_api_formresults' => [
                'path'       => '/forms/{formId}/submissions',
                'controller' => 'Mautic\FormBundle\Controller\Api\SubmissionApiController::getEntitiesAction',
            ],
            'mautic_api_formresult' => [
                'path'       => '/forms/{formId}/submissions/{submissionId}',
                'controller' => 'Mautic\FormBundle\Controller\Api\SubmissionApiController::getEntityAction',
            ],
            'mautic_api_contactformresults' => [
                'path'       => '/forms/{formId}/submissions/contact/{contactId}',
                'controller' => 'Mautic\FormBundle\Controller\Api\SubmissionApiController::getEntitiesForContactAction',
            ],
            'mautic_api_formdeletefields' => [
                'path'       => '/forms/{formId}/fields/delete',
                'controller' => 'Mautic\FormBundle\Controller\Api\FormApiController::deleteFieldsAction',
                'method'     => 'DELETE',
            ],
            'mautic_api_formdeleteactions' => [
                'path'       => '/forms/{formId}/actions/delete',
                'controller' => 'Mautic\FormBundle\Controller\Api\FormApiController::deleteActionsAction',
                'method'     => 'DELETE',
            ],
        ],
        'public' => [
            'mautic_form_file_download' => [
                'path'       => '/forms/results/file/{submissionId}/{field}',
                'controller' => 'Mautic\FormBundle\Controller\ResultController::downloadFileAction',
            ],
            'mautic_form_file_download_by_name' => [
                'path'       => '/forms/results/file/{fieldId}/filename/{fileName}',
                'controller' => 'Mautic\FormBundle\Controller\ResultController::downloadFileByFileNameAction',
            ],
            'mautic_form_postresults' => [
                'path'       => '/form/submit',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::submitAction',
            ],
            'mautic_form_generateform' => [
                'path'       => '/form/generate.js',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::generateAction',
            ],
            'mautic_form_postmessage' => [
                'path'       => '/form/message',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::messageAction',
            ],
            'mautic_form_preview' => [
                'path'       => '/form/{id}',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::previewAction',
                'defaults'   => [
                    'id' => '0',
                ],
            ],
            'mautic_form_embed' => [
                'path'       => '/form/embed/{id}',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::embedAction',
            ],
            'mautic_form_postresults_ajax' => [
                'path'       => '/form/submit/ajax',
                'controller' => 'Mautic\FormBundle\Controller\AjaxController::submitAction',
            ],
            'mautic_form_company_lookup' => [
                'path'       => '/form/company-lookup/autocomplete',
                'controller' => 'Mautic\FormBundle\Controller\PublicController::lookupCompanyAction',
                'method'     => 'POST',
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

    'services' => [
        'forms' => [
            'mautic.form.type.field' => [
                'class'       => FieldType::class,
                'arguments'   => [
                    'translator',
                    'mautic.form.collector.object',
                    'mautic.form.collector.field',
                    'mautic.form.collector.already.mapped.field',
                ],
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.form_submitaction_sendemail' => [
                'class'       => SubmitActionEmailType::class,
                'arguments'   => [
                    'translator',
                    'mautic.helper.core_parameters',
                ],
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.form_submitaction_repost' => [
                'class'       => SubmitActionRepostType::class,
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
        ],
        'other' => [
            'mautic.form.collector.object' => [
                'class'     => Mautic\FormBundle\Collector\ObjectCollector::class,
                'arguments' => ['event_dispatcher'],
            ],
            'mautic.form.collector.field' => [
                'class'     => Mautic\FormBundle\Collector\FieldCollector::class,
                'arguments' => ['event_dispatcher'],
            ],
            'mautic.form.collector.mapped.object' => [
                'class'     => Mautic\FormBundle\Collector\MappedObjectCollector::class,
                'arguments' => ['mautic.form.collector.field'],
            ],
            'mautic.form.collector.already.mapped.field' => [
                'class'     => Mautic\FormBundle\Collector\AlreadyMappedFieldCollector::class,
                'arguments' => ['mautic.cache.provider'],
            ],
            'mautic.helper.form.field_helper' => [
                'class'     => FormFieldHelper::class,
                'arguments' => [
                    'translator',
                    'validator',
                ],
            ],
            'mautic.form.helper.form_uploader' => [
                'class'     => FormUploader::class,
                'arguments' => [
                    'mautic.helper.file_uploader',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.helper.token' => [
                'class'     => TokenHelper::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.security',
                ],
            ],
            'mautic.form.service.field.value.transformer' => [
                'class'     => FieldValueTransformer::class,
                'arguments' => [
                    'router',
                ],
            ],
            'mautic.form.helper.properties.accessor' => [
                'class'     => Mautic\FormBundle\Helper\PropertiesAccessor::class,
                'arguments' => [
                    'mautic.form.model.form',
                ],
            ],
        ],
        'validator' => [
            'mautic.form.validator.upload_field_validator' => [
                'class'     => UploadFieldValidator::class,
                'arguments' => [
                    'mautic.core.validator.file_upload',
                ],
            ],
            'mautic.form.validator.constraint.file_extension_constraint_validator' => [
                'class'     => FileExtensionConstraintValidator::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
                'tags' => [
                    'name'  => 'validator.constraint_validator',
                    'alias' => 'file_extension_constraint_validator',
                ],
            ],
        ],
        'fixtures' => [
            'mautic.form.fixture.form' => [
                'class'     => Mautic\FormBundle\DataFixtures\ORM\LoadFormData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.form.model.form', 'mautic.form.model.field', 'mautic.form.model.action', 'event_dispatcher'],
            ],
            'mautic.form.fixture.form_result' => [
                'class'     => Mautic\FormBundle\DataFixtures\ORM\LoadFormResultData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page', 'mautic.form.model.submission'],
            ],
        ],
    ],

    'parameters' => [
        'form_upload_dir'           => '%mautic.application_dir%/media/files/form',
        'blacklisted_extensions'    => ['php', 'sh'],
        'do_not_submit_emails'      => [],
        'form_results_data_sources' => false,
        'successful_submit_action'  => 'top',
    ],
];
