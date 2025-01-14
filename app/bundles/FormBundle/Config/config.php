<?php

use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\EventListener\CampaignSubscriber;
use Mautic\FormBundle\EventListener\DashboardSubscriber;
use Mautic\FormBundle\EventListener\EmailSubscriber;
use Mautic\FormBundle\EventListener\FormSubscriber;
use Mautic\FormBundle\EventListener\FormValidationSubscriber;
use Mautic\FormBundle\EventListener\LeadSubscriber;
use Mautic\FormBundle\EventListener\PageSubscriber;
use Mautic\FormBundle\EventListener\PointSubscriber;
use Mautic\FormBundle\EventListener\ReportSubscriber;
use Mautic\FormBundle\EventListener\SearchSubscriber;
use Mautic\FormBundle\EventListener\StatsSubscriber;
use Mautic\FormBundle\EventListener\WebhookSubscriber;
use Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\Form\Type\FormFieldPageBreakType;
use Mautic\FormBundle\Form\Type\FormFieldTelType;
use Mautic\FormBundle\Form\Type\FormListType;
use Mautic\FormBundle\Form\Type\FormType;
use Mautic\FormBundle\Form\Type\SubmitActionEmailType;
use Mautic\FormBundle\Form\Type\SubmitActionRepostType;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Helper\TokenHelper;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Model\SubmissionResultLoader;
use Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator;
use Mautic\FormBundle\Validator\UploadFieldValidator;

return [
    'routes' => [
        'main' => [
            'mautic_formaction_action' => [
                'path'       => '/forms/action/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Action:execute',
            ],
            'mautic_formfield_action' => [
                'path'       => '/forms/field/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Field:execute',
            ],
            'mautic_form_index' => [
                'path'       => '/forms/{page}',
                'controller' => 'MauticFormBundle:Form:index',
            ],
            'mautic_form_results' => [
                'path'       => '/forms/results/{objectId}/{page}',
                'controller' => 'MauticFormBundle:Result:index',
            ],
            'mautic_form_export' => [
                'path'       => '/forms/results/{objectId}/export/{format}',
                'controller' => 'MauticFormBundle:Result:export',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
            'mautic_form_results_action' => [
                'path'       => '/forms/results/{formId}/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Result:execute',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
            'mautic_form_action' => [
                'path'       => '/forms/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Form:execute',
            ],
        ],
        'api' => [
            'mautic_api_formstandard' => [
                'standard_entity' => true,
                'name'            => 'forms',
                'path'            => '/forms',
                'controller'      => 'MauticFormBundle:Api\FormApi',
            ],
            'mautic_api_formresults' => [
                'path'       => '/forms/{formId}/submissions',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntities',
            ],
            'mautic_api_formresult' => [
                'path'       => '/forms/{formId}/submissions/{submissionId}',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntity',
            ],
            'mautic_api_contactformresults' => [
                'path'       => '/forms/{formId}/submissions/contact/{contactId}',
                'controller' => 'MauticFormBundle:Api\SubmissionApi:getEntitiesForContact',
            ],
            'mautic_api_formdeletefields' => [
                'path'       => '/forms/{formId}/fields/delete',
                'controller' => 'MauticFormBundle:Api\FormApi:deleteFields',
                'method'     => 'DELETE',
            ],
            'mautic_api_formdeleteactions' => [
                'path'       => '/forms/{formId}/actions/delete',
                'controller' => 'MauticFormBundle:Api\FormApi:deleteActions',
                'method'     => 'DELETE',
            ],
        ],
        'public' => [
            'mautic_form_file_download' => [
                'path'       => '/forms/results/file/{submissionId}/{field}',
                'controller' => 'MauticFormBundle:Result:downloadFile',
            ],
            'mautic_form_postresults' => [
                'path'       => '/form/submit',
                'controller' => 'MauticFormBundle:Public:submit',
            ],
            'mautic_form_generateform' => [
                'path'       => '/form/generate.js',
                'controller' => 'MauticFormBundle:Public:generate',
            ],
            'mautic_form_postmessage' => [
                'path'       => '/form/message',
                'controller' => 'MauticFormBundle:Public:message',
            ],
            'mautic_form_preview' => [
                'path'       => '/form/{id}',
                'controller' => 'MauticFormBundle:Public:preview',
                'defaults'   => [
                    'id' => '0',
                ],
            ],
            'mautic_form_embed' => [
                'path'       => '/form/embed/{id}',
                'controller' => 'MauticFormBundle:Public:embed',
            ],
            'mautic_form_postresults_ajax' => [
                'path'       => '/form/submit/ajax',
                'controller' => 'MauticFormBundle:Ajax:submit',
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
        'form' => null,
    ],

    'services' => [
        'events' => [
            'mautic.core.configbundle.subscriber.form' => [
                'class'     => \Mautic\FormBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.form.subscriber' => [
                'class'     => FormSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.helper.mailer',
                    'mautic.helper.core_parameters',
                    'translator',
                    'router',
                ],
            ],
            'mautic.form.validation.subscriber' => [
                'class'     => FormValidationSubscriber::class,
                'arguments' => [
                    'translator',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.pagebundle.subscriber' => [
                'class'     => PageSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.helper.token_builder.factory',
                    'translator',
                    'mautic.security',
                ],
            ],
            'mautic.form.pointbundle.subscriber' => [
                'class'     => PointSubscriber::class,
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.form.reportbundle.subscriber' => [
                'class'     => ReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.company_report_data',
                    'mautic.form.repository.submission',
                ],
            ],
            'mautic.form.campaignbundle.subscriber' => [
                'class'     => CampaignSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'mautic.campaign.executioner.realtime',
                    'mautic.helper.form.field_helper',
                ],
            ],
            'mautic.form.leadbundle.subscriber' => [
                'class'     => LeadSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                    'mautic.form.repository.submission',
                    'translator',
                    'router',
                ],
            ],
            'mautic.form.emailbundle.subscriber' => [
                'class' => EmailSubscriber::class,
            ],
            'mautic.form.search.subscriber' => [
                'class'     => SearchSubscriber::class,
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.form.model.form',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.form.webhook.subscriber' => [
                'class'     => WebhookSubscriber::class,
                'arguments' => [
                    'mautic.webhook.model.webhook',
                ],
            ],
            'mautic.form.dashboard.subscriber' => [
                'class'     => DashboardSubscriber::class,
                'arguments' => [
                    'mautic.form.model.submission',
                    'mautic.form.model.form',
                    'router',
                ],
            ],
            'mautic.form.stats.subscriber' => [
                'class'     => StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.form.subscriber.determine_winner' => [
                'class'     => \Mautic\FormBundle\EventListener\DetermineWinnerSubscriber::class,
                'arguments' => [
                    'mautic.form.repository.submission',
                    'translator',
                ],
            ],
            'mautic.form.conditional.subscriber' => [
                'class'     => \Mautic\FormBundle\EventListener\FormConditionalSubscriber::class,
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.form.model.field',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.formconfig' => [
                'class'     => \Mautic\FormBundle\Form\Type\ConfigFormType::class,
                    'alias' => 'formconfig',
            ],
            'mautic.form.type.form' => [
                'class'     => FormType::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'mautic.form.type.field' => [
                'class'       => FieldType::class,
                'arguments'   => [
                    'translator',
                ],
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.field_propertypagebreak' => [
                'class'     => FormFieldPageBreakType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertytel' => [
                'class'     => FormFieldTelType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertyemail' => [
                'class'     => \Mautic\FormBundle\Form\Type\FormFieldEmailType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertyfile' => [
                'class'     => FormFieldFileType::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'translator',
                ],
            ],
            'mautic.form.type.form_list' => [
                'class'     => FormListType::class,
                'arguments' => [
                    'mautic.security',
                    'mautic.form.model.form',
                    'mautic.helper.user',
                ],
            ],
            'mautic.form.type.campaignevent_form_field_value' => [
                'class'     => CampaignEventFormFieldValueType::class,
                'arguments' => [
                    'mautic.form.model.form',
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
            'mautic.form.type.field.conditional' => [
                'class'       => \Mautic\FormBundle\Form\Type\FormFieldConditionType::class,
                'arguments'   => [
                    'mautic.form.model.field',
                    'mautic.form.helper.properties.accessor',
                ],
            ],
        ],
        'models' => [
            'mautic.form.model.action' => [
                'class' => ActionModel::class,
            ],
            'mautic.form.model.field' => [
                'class'     => FieldModel::class,
                'arguments' => [
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.form.model.form' => [
                'class'     => FormModel::class,
                'arguments' => [
                    'request_stack',
                    'mautic.helper.templating',
                    'mautic.helper.theme',
                    'mautic.form.model.action',
                    'mautic.form.model.field',
                    'mautic.helper.form.field_helper',
                    'mautic.lead.model.field',
                    'mautic.form.helper.form_uploader',
                    'mautic.tracker.contact',
                    'mautic.schema.helper.column',
                    'mautic.schema.helper.table',
                ],
            ],
            'mautic.form.model.submission' => [
                'class'     => SubmissionModel::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.templating',
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.campaign.membership.manager',
                    'mautic.lead.model.field',
                    'mautic.lead.model.company',
                    'mautic.helper.form.field_helper',
                    'mautic.form.validator.upload_field_validator',
                    'mautic.form.helper.form_uploader',
                    'mautic.lead.service.device_tracking_service',
                    'mautic.form.service.field.value.transformer',
                    'mautic.helper.template.date',
                    'mautic.tracker.contact',
                ],
            ],
            'mautic.form.model.submission_result_loader' => [
                'class'     => SubmissionResultLoader::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'repositories' => [
            'mautic.form.repository.form' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => \Mautic\FormBundle\Entity\Form::class,
            ],
            'mautic.form.repository.submission' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => \Mautic\FormBundle\Entity\Submission::class,
            ],
        ],
        'other' => [
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
                'class'     => \Mautic\FormBundle\Helper\PropertiesAccessor::class,
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
                'class'     => \Mautic\FormBundle\DataFixtures\ORM\LoadFormData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.form.model.form', 'mautic.form.model.field', 'mautic.form.model.action'],
            ],
            'mautic.form.fixture.form_result' => [
                'class'     => \Mautic\FormBundle\DataFixtures\ORM\LoadFormResultData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page', 'mautic.form.model.submission'],
            ],
        ],
    ],

    'parameters' => [
        'form_upload_dir'        => '%kernel.root_dir%/../media/files/form',
        'blacklisted_extensions' => ['php', 'sh'],
        'do_not_submit_emails'   => [],
    ],
];
