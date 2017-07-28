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
            'mautic_email_index' => [
                'path'       => '/emails/{page}',
                'controller' => 'MauticEmailBundle:Email:index',
            ],
            'mautic_email_action' => [
                'path'       => '/emails/{objectAction}/{objectId}',
                'controller' => 'MauticEmailBundle:Email:execute',
            ],
        ],
        'api' => [
            'mautic_api_emailstandard' => [
                'standard_entity' => true,
                'name'            => 'emails',
                'path'            => '/emails',
                'controller'      => 'MauticEmailBundle:Api\EmailApi',
            ],
            'mautic_api_sendemail' => [
                'path'       => '/emails/{id}/send',
                'controller' => 'MauticEmailBundle:Api\EmailApi:send',
                'method'     => 'POST',
            ],
            'mautic_api_sendcontactemail' => [
                'path'       => '/emails/{id}/contact/{leadId}/send',
                'controller' => 'MauticEmailBundle:Api\EmailApi:sendLead',
                'method'     => 'POST',
            ],

            // @deprecated 2.6.0 to be removed in 3.0
            'bc_mautic_api_sendcontactemail' => [
                'path'       => '/emails/{id}/send/contact/{leadId}',
                'controller' => 'MauticEmailBundle:Api\EmailApi:sendLead',
                'method'     => 'POST',
            ],
        ],
        'public' => [
            'mautic_plugin_tracker' => [
                'path'         => '/plugin/{integration}/tracking.gif',
                'controller'   => 'MauticEmailBundle:Public:pluginTrackingGif',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_email_tracker' => [
                'path'       => '/email/{idHash}.gif',
                'controller' => 'MauticEmailBundle:Public:trackingImage',
            ],
            'mautic_email_webview' => [
                'path'       => '/email/view/{idHash}',
                'controller' => 'MauticEmailBundle:Public:index',
            ],
            'mautic_email_unsubscribe' => [
                'path'       => '/email/unsubscribe/{idHash}',
                'controller' => 'MauticEmailBundle:Public:unsubscribe',
            ],
            'mautic_email_resubscribe' => [
                'path'       => '/email/resubscribe/{idHash}',
                'controller' => 'MauticEmailBundle:Public:resubscribe',
            ],
            'mautic_mailer_transport_callback' => [
                'path'       => '/mailer/{transport}/callback',
                'controller' => 'MauticEmailBundle:Public:mailerCallback',
            ],
            'mautic_email_preview' => [
                'path'       => '/email/preview/{objectId}',
                'controller' => 'MauticEmailBundle:Public:preview',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'items' => [
                'mautic.email.emails' => [
                    'route'    => 'mautic_email_index',
                    'access'   => ['email:emails:viewown', 'email:emails:viewother'],
                    'parent'   => 'mautic.core.channels',
                    'priority' => 100,
                ],
            ],
        ],
    ],
    'categories' => [
        'email' => null,
    ],
    'services' => [
        'events' => [
            'mautic.email.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\EmailSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.email.model.email',
                ],
            ],
            'mautic.emailbuilder.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\BuilderSubscriber',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.email.model.email',
                    'mautic.page.model.trackable',
                    'mautic.page.model.redirect',
                ],
            ],
            'mautic.emailtoken.subscriber' => [
                'class' => 'Mautic\EmailBundle\EventListener\TokenSubscriber',
            ],
            'mautic.email.campaignbundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.email.model.email',
                    'mautic.campaign.model.event',
                    'mautic.channel.model.queue',
                ],
            ],
            'mautic.email.formbundle.subscriber' => [
                'class' => 'Mautic\EmailBundle\EventListener\FormSubscriber',
            ],
            'mautic.email.reportbundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\ReportSubscriber',
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.email.leadbundle.subscriber' => [
                'class' => 'Mautic\EmailBundle\EventListener\LeadSubscriber',
            ],
            'mautic.email.pointbundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.email.calendarbundle.subscriber' => [
                'class' => 'Mautic\EmailBundle\EventListener\CalendarSubscriber',
            ],
            'mautic.email.search.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.webhook.subscriber' => [
                'class'       => 'Mautic\EmailBundle\EventListener\WebhookSubscriber',
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
            'mautic.email.configbundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.email.pagebundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.dashboard.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.broadcast.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\BroadcastSubscriber',
                'arguments' => [
                    'mautic.email.model.email',
                    'doctrine.orm.entity_manager',
                    'translator',
                    'mautic.lead.model.lead',
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.messagequeue.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\MessageQueueSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.channel.subscriber' => [
                'class' => \Mautic\EmailBundle\EventListener\ChannelSubscriber::class,
            ],
            'mautic.email.stats.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.email' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\EmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailform',
            ],
            'mautic.form.type.email.utm_tags' => [
                'class' => 'Mautic\EmailBundle\Form\Type\EmailUtmTagsType',
                'alias' => 'utm_tags',
            ],
            'mautic.form.type.emailvariant' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\VariantType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailvariant',
            ],
            'mautic.form.type.email_list' => [
                'class' => 'Mautic\EmailBundle\Form\Type\EmailListType',
                'alias' => 'email_list',
            ],
            'mautic.form.type.emailopen_list' => [
                'class' => 'Mautic\EmailBundle\Form\Type\EmailOpenType',
                'alias' => 'emailopen_list',
            ],
            'mautic.form.type.emailsend_list' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\EmailSendType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailsend_list',
            ],
            'mautic.form.type.formsubmit_sendemail_admin' => [
                'class' => 'Mautic\EmailBundle\Form\Type\FormSubmitActionUserEmailType',
                'alias' => 'email_submitaction_useremail',
            ],
            'mautic.email.type.email_abtest_settings' => [
                'class' => 'Mautic\EmailBundle\Form\Type\AbTestPropertiesType',
                'alias' => 'email_abtest_settings',
            ],
            'mautic.email.type.batch_send' => [
                'class' => 'Mautic\EmailBundle\Form\Type\BatchSendType',
                'alias' => 'batch_send',
            ],
            'mautic.form.type.emailconfig' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailconfig',
            ],
            'mautic.form.type.coreconfig_monitored_mailboxes' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\ConfigMonitoredMailboxesType',
                'arguments' => 'mautic.factory',
                'alias'     => 'monitored_mailboxes',
            ],
            'mautic.form.type.coreconfig_monitored_email' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\ConfigMonitoredEmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'monitored_email',
            ],
            'mautic.form.type.email_dashboard_emails_in_time_widget' => [
                'class' => 'Mautic\EmailBundle\Form\Type\DashboardEmailsInTimeWidgetType',
                'alias' => 'email_dashboard_emails_in_time_widget',
            ],
            'mautic.form.type.email_to_user' => [
                'class' => Mautic\EmailBundle\Form\Type\EmailToUserType::class,
                'alias' => 'email_to_user',
            ],
        ],
        'other' => [
            'mautic.validator.leadlistaccess' => [
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access',
            ],
            'mautic.helper.mailbox' => [
                'class'     => 'Mautic\EmailBundle\MonitoredEmail\Mailbox',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.paths',
                ],
            ],
            'mautic.helper.message' => [
                'class'     => 'Mautic\EmailBundle\Helper\MessageHelper',
                'arguments' => 'mautic.factory',
            ],
            'mautic.helper.mailer' => [
                'class'     => 'Mautic\EmailBundle\Helper\MailHelper',
                'arguments' => [
                    'mautic.factory',
                    'mailer',
                ],
            ],
            // Mailers
            'mautic.transport.amazon' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\AmazonTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    '%mautic.mailer_amazon_region%',
                    'mautic.http.connector',
                ],
                'methodCalls' => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.mandrill' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\MandrillTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => [
                    'setUsername'      => ['%mautic.mailer_user%'],
                    'setPassword'      => ['%mautic.mailer_api_key%'],
                    'setMauticFactory' => ['mautic.factory'],
                ],
            ],
            'mautic.transport.mailjet' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\MailjetTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    '',
                    '',
                    '',
                    '%mautic.mailer_mailjet_sandbox%',
                    '%mautic.mailer_mailjet_sandbox_default_mail%',
                ],
                'methodCalls' => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.sendgrid' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\SendgridTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.elasticemail' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\ElasticemailTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.postmark' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\PostmarkTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'methodCalls'  => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.sparkpost' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\SparkpostTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    '%mautic.mailer_api_key%',
                    'translator',
                ],
                'methodCalls' => [
                    'setMauticFactory' => ['mautic.factory'],
                ],
            ],
        ],
        'models' => [
            'mautic.email.model.email' => [
                'class'     => 'Mautic\EmailBundle\Model\EmailModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.helper.theme',
                    'mautic.helper.mailbox',
                    'mautic.helper.mailer',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.page.model.trackable',
                    'mautic.user.model.user',
                    'mautic.channel.model.queue',
                ],
            ],
        ],
    ],
    'parameters' => [
        'mailer_api_key'                      => null, // Api key from mail delivery provider.
        'mailer_from_name'                    => 'Mautic',
        'mailer_from_email'                   => 'email@yoursite.com',
        'mailer_return_path'                  => null,
        'mailer_transport'                    => 'mail',
        'mailer_append_tracking_pixel'        => true,
        'mailer_convert_embed_images'         => false,
        'mailer_host'                         => '',
        'mailer_port'                         => null,
        'mailer_user'                         => null,
        'mailer_password'                     => null,
        'mailer_encryption'                   => null, //tls or ssl,
        'mailer_auth_mode'                    => null, //plain, login or cram-md5
        'mailer_amazon_region'                => 'email-smtp.us-east-1.amazonaws.com',
        'mailer_spool_type'                   => 'memory', //memory = immediate; file = queue
        'mailer_spool_path'                   => '%kernel.root_dir%/spool',
        'mailer_spool_msg_limit'              => null,
        'mailer_spool_time_limit'             => null,
        'mailer_spool_recover_timeout'        => 900,
        'mailer_spool_clear_timeout'          => 1800,
        'unsubscribe_text'                    => null,
        'webview_text'                        => null,
        'unsubscribe_message'                 => null,
        'resubscribe_message'                 => null,
        'monitored_email'                     => [],
        'mailer_is_owner'                     => false,
        'default_signature_text'              => null,
        'email_frequency_number'              => null,
        'email_frequency_time'                => null,
        'show_contact_preferences'            => false,
        'show_contact_frequency'              => false,
        'show_contact_pause_dates'            => false,
        'show_contact_preferred_channels'     => false,
        'show_contact_categories'             => false,
        'show_contact_segments'               => false,
        'mailer_mailjet_sandbox'              => false,
        'mailer_mailjet_sandbox_default_mail' => null,
        'disable_trackable_urls'              => false,

    ],
];
