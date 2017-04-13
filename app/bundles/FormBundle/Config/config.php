<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
            'mautic.form.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.helper.mailer',
                ],
            ],
            'mautic.form.pagebundle.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.pointbundle.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.form.reportbundle.subscriber' => [
                'class' => 'Mautic\FormBundle\EventListener\ReportSubscriber',
            ],
            'mautic.form.campaignbundle.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'mautic.campaign.model.event',
                ],
            ],
            'mautic.form.calendarbundle.subscriber' => [
                'class' => 'Mautic\FormBundle\EventListener\CalendarSubscriber',
            ],
            'mautic.form.leadbundle.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                ],
            ],
            'mautic.form.emailbundle.subscriber' => [
                'class' => 'Mautic\FormBundle\EventListener\EmailSubscriber',
            ],
            'mautic.form.search.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.webhook.subscriber' => [
                'class'       => 'Mautic\FormBundle\EventListener\WebhookSubscriber',
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
            'mautic.form.dashboard.subscriber' => [
                'class'     => 'Mautic\FormBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.form.model.submission',
                    'mautic.form.model.form',
                ],
            ],
            'mautic.form.stats.subscriber' => [
                'class'     => \Mautic\FormBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.form' => [
                'class'     => 'Mautic\FormBundle\Form\Type\FormType',
                'arguments' => 'mautic.factory',
                'alias'     => 'mauticform',
            ],
            'mautic.form.type.field' => [
                'class'       => 'Mautic\FormBundle\Form\Type\FieldType',
                'alias'       => 'formfield',
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.action' => [
                'class' => 'Mautic\FormBundle\Form\Type\ActionType',
                'alias' => 'formaction',
            ],
            'mautic.form.type.field_propertytext' => [
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldTextType',
                'alias' => 'formfield_text',
            ],
            'mautic.form.type.field_propertyplaceholder' => [
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldPlaceholderType',
                'alias' => 'formfield_placeholder',
            ],
            'mautic.form.type.field_propertyselect' => [
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldSelectType',
                'alias' => 'formfield_select',
            ],
            'mautic.form.type.field_propertycaptcha' => [
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldCaptchaType',
                'alias' => 'formfield_captcha',
            ],
            'muatic.form.type.field_propertypagebreak' => [
                'class'     => \Mautic\FormBundle\Form\Type\FormFieldPageBreakType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.form.type.field_propertygroup' => [
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldGroupType',
                'alias' => 'formfield_group',
            ],
            'mautic.form.type.pointaction_formsubmit' => [
                'class' => 'Mautic\FormBundle\Form\Type\PointActionFormSubmitType',
                'alias' => 'pointaction_formsubmit',
            ],
            'mautic.form.type.form_list' => [
                'class'     => 'Mautic\FormBundle\Form\Type\FormListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'form_list',
            ],
            'mautic.form.type.campaignevent_formsubmit' => [
                'class' => 'Mautic\FormBundle\Form\Type\CampaignEventFormSubmitType',
                'alias' => 'campaignevent_formsubmit',
            ],
            'mautic.form.type.campaignevent_form_field_value' => [
                'class'     => 'Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType',
                'arguments' => 'mautic.factory',
                'alias'     => 'campaignevent_form_field_value',
            ],
            'mautic.form.type.form_submitaction_sendemail' => [
                'class'       => 'Mautic\FormBundle\Form\Type\SubmitActionEmailType',
                'arguments'   => 'translator',
                'alias'       => 'form_submitaction_sendemail',
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
            'mautic.form.type.form_submitaction_repost' => [
                'class'       => \Mautic\FormBundle\Form\Type\SubmitActionRepostType::class,
                'methodCalls' => [
                    'setFieldModel' => ['mautic.form.model.field'],
                    'setFormModel'  => ['mautic.form.model.form'],
                ],
            ],
        ],
        'models' => [
            'mautic.form.model.action' => [
                'class' => 'Mautic\FormBundle\Model\ActionModel',
            ],
            'mautic.form.model.field' => [
                'class'     => 'Mautic\FormBundle\Model\FieldModel',
                'arguments' => [
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.form.model.form' => [
                'class'     => 'Mautic\FormBundle\Model\FormModel',
                'arguments' => [
                    'request_stack',
                    'mautic.helper.templating',
                    'mautic.helper.theme',
                    'mautic.schema.helper.factory',
                    'mautic.form.model.action',
                    'mautic.form.model.field',
                    'mautic.lead.model.lead',
                    'mautic.helper.form.field_helper',
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.form.model.submission' => [
                'class'     => 'Mautic\FormBundle\Model\SubmissionModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.templating',
                    'mautic.form.model.form',
                    'mautic.page.model.page',
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.lead.model.field',
                    'mautic.lead.model.company',
                    'mautic.helper.form.field_helper',
                ],
            ],
        ],
        'other' => [
            'mautic.helper.form.field_helper' => [
                'class'     => \Mautic\FormBundle\Helper\FormFieldHelper::class,
                'arguments' => [
                    'translator',
                    'validator',
                ],
            ],
            'mautic.form.helper.token' => [
                'class'     => 'Mautic\FormBundle\Helper\TokenHelper',
                'arguments' => [
                    'mautic.form.model.form',
                ],
            ],
        ],
    ],
];
