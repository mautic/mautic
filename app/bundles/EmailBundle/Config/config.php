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
            'mautic_email_graph_stats' => [
                'path'       => '/emails-graph-stats/{objectId}/{isVariant}/{dateFrom}/{dateTo}',
                'controller' => 'MauticEmailBundle:EmailGraphStats:view',
            ],
            'mautic_email_action' => [
                'path'       => '/emails/{objectAction}/{objectId}',
                'controller' => 'MauticEmailBundle:Email:execute',
            ],
            'mautic_email_contacts' => [
                'path'       => '/emails/view/{objectId}/contact/{page}',
                'controller' => 'MauticEmailBundle:Email:contacts',
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
                'method'     => ['GET', 'POST'],
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
                    'mautic.helper.message',
                ],
            ],
            'mautic.email.queue.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\QueueSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.momentum.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\MomentumSubscriber::class,
                'arguments' => [
                    'mautic.transport.momentum.callback',
                    'mautic.queue.service',
                    'mautic.email.helper.request.storage',
                ],
            ],
            'mautic.email.monitored.bounce.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\ProcessBounceSubscriber::class,
                'arguments' => [
                    'mautic.message.processor.bounce',
                ],
            ],
            'mautic.email.monitored.unsubscribe.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber::class,
                'arguments' => [
                    'mautic.message.processor.unsubscribe',
                    'mautic.message.processor.feedbackloop',
                ],
            ],
            'mautic.email.monitored.unsubscribe.replier' => [
                'class'     => \Mautic\EmailBundle\EventListener\ProcessReplySubscriber::class,
                'arguments' => [
                    'mautic.message.processor.replier',
                    'mautic.helper.cache_storage',
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
                'class'     => 'Mautic\EmailBundle\EventListener\TokenSubscriber',
                'arguments' => [
                    'event_dispatcher',
                    'mautic.lead.helper.primary_company',
                ],
            ],
            'mautic.email.campaignbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.email.model.email',
                    'mautic.campaign.model.event',
                    'mautic.channel.model.queue',
                    'mautic.email.model.send_email_to_user',
                    'translator',
                ],
            ],
            'mautic.email.campaignbundle.condition_subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\CampaignConditionSubscriber',
                'arguments' => [
                    'mautic.validator.email',
                ],
            ],
            'mautic.email.formbundle.subscriber' => [
                'class' => 'Mautic\EmailBundle\EventListener\FormSubscriber',
            ],
            'mautic.email.reportbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                    'mautic.lead.model.company_report_data',
                ],
            ],
            'mautic.email.leadbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.email.repository.emailReply',
                ],
            ],
            'mautic.email.pointbundle.subscriber' => [
                'class'     => 'Mautic\EmailBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.email.touser.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\EmailToUserSubscriber::class,
                'arguments' => [
                    'mautic.email.model.send_email_to_user',
                ],
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
                    'mautic.campaign.model.event',
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
            'mautic.email.subscriber.contact_tracker' => [
                'class'     => \Mautic\EmailBundle\EventListener\TrackingSubscriber::class,
                'arguments' => [
                    'mautic.email.repository.stat',
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
            'mautic.form.type.email_click_decision' => [
                'class' => 'Mautic\EmailBundle\Form\Type\EmailClickDecisionType',
                'alias' => 'email_click_decision',
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
                'class'     => \Mautic\EmailBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'translator',
                    'mautic.email.transport_type',
                ],
                'alias'     => 'emailconfig',
            ],
            'mautic.form.type.coreconfig_monitored_mailboxes' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\ConfigMonitoredMailboxesType',
                'arguments' => [
                    'mautic.helper.mailbox',
                ],
                'alias' => 'monitored_mailboxes',
            ],
            'mautic.form.type.coreconfig_monitored_email' => [
                'class'     => \Mautic\EmailBundle\Form\Type\ConfigMonitoredEmailType::class,
                'arguments' => 'event_dispatcher',
                'alias'     => 'monitored_email',
            ],
            'mautic.form.type.email_dashboard_emails_in_time_widget' => [
                'class'     => 'Mautic\EmailBundle\Form\Type\DashboardEmailsInTimeWidgetType',
                'alias'     => 'email_dashboard_emails_in_time_widget',
            ],
            'mautic.form.type.email_dashboard_sent_email_to_contacts_widget' => [
                'class'     => \Mautic\EmailBundle\Form\Type\DashboardSentEmailToContactsWidgetType::class,
                'alias'     => 'email_dashboard_sent_email_to_contacts_widget',
            ],
            'mautic.form.type.email_dashboard_most_hit_email_redirects_widget' => [
                'class'     => \Mautic\EmailBundle\Form\Type\DashboardMostHitEmailRedirectsWidgetType::class,
                'alias'     => 'email_dashboard_most_hit_email_redirects_widget',
            ],
            'mautic.form.type.email_to_user' => [
                'class' => Mautic\EmailBundle\Form\Type\EmailToUserType::class,
                'alias' => 'email_to_user',
            ],
        ],
        'other' => [
            // Mailers
            'mautic.transport.amazon' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\AmazonTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    '%mautic.mailer_amazon_region%',
                    'mautic.http.connector',
                    'monolog.logger.mautic',
                    'translator',
                    'mautic.email.model.transport_callback',
                ],
                'methodCalls' => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.mandrill' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\MandrillTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    'translator',
                    'mautic.email.model.transport_callback',
                ],
                'methodCalls'  => [
                    'setUsername'      => ['%mautic.mailer_user%'],
                    'setPassword'      => ['%mautic.mailer_api_key%'],
                ],
            ],
            'mautic.transport.mailjet' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\MailjetTransport',
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    'mautic.email.model.transport_callback',
                    '%mautic.mailer_mailjet_sandbox%',
                    '%mautic.mailer_mailjet_sandbox_default_mail%',
                ],
                'methodCalls' => [
                    'setUsername' => ['%mautic.mailer_user%'],
                    'setPassword' => ['%mautic.mailer_password%'],
                ],
            ],
            'mautic.transport.momentum' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Transport\MomentumTransport::class,
                'arguments' => [
                    'mautic.transport.momentum.callback',
                    'mautic.transport.momentum.facade',
                ],
                'tag'          => 'mautic.email_transport',
                'tagArguments' => [
                    \Mautic\EmailBundle\Model\TransportType::TRANSPORT_ALIAS => 'mautic.email.config.mailer_transport.momentum',
                    \Mautic\EmailBundle\Model\TransportType::FIELD_HOST      => true,
                    \Mautic\EmailBundle\Model\TransportType::FIELD_PORT      => true,
                    \Mautic\EmailBundle\Model\TransportType::FIELD_API_KEY   => true,
                ],
            ],
            'mautic.transport.momentum.adapter' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\Adapter::class,
                'arguments' => [
                    'mautic.transport.momentum.sparkpost',
                ],
            ],
            'mautic.transport.momentum.service.swift_message' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageService::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.transport.momentum.validator.swift_message' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator\SwiftMessageValidator::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.transport.momentum.callback' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallback::class,
                'arguments' => [
                    'mautic.email.model.transport_callback',
                ],
            ],
            'mautic.transport.momentum.facade' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Momentum\Facade\MomentumFacade::class,
                'arguments' => [
                    'mautic.transport.momentum.adapter',
                    'mautic.transport.momentum.service.swift_message',
                    'mautic.transport.momentum.validator.swift_message',
                    'mautic.transport.momentum.callback',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.transport.momentum.sparkpost' => [
                'class'     => \SparkPost\SparkPost::class,
                'factory'   => ['@mautic.sparkpost.factory', 'create'],
                'arguments' => [
                    '%mautic.mailer_host%',
                    '%mautic.mailer_api_key%',
                    '%mautic.mailer_port%',
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
            'mautic.transport.sendgrid_api' => [
                'class'        => \Mautic\EmailBundle\Swiftmailer\Transport\SendgridApiTransport::class,
                'serviceAlias' => 'swiftmailer.mailer.transport.%s',
                'arguments'    => [
                    'mautic.transport.sendgrid_api.facade',
                    'mautic.transport.sendgrid_api.calback',
                ],
            ],
            'mautic.transport.sendgrid_api.facade' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiFacade::class,
                'arguments' => [
                    'mautic.transport.sendgrid_api.sendgrid_wrapper',
                    'mautic.transport.sendgrid_api.message',
                    'mautic.transport.sendgrid_api.response',
                ],
            ],
            'mautic.transport.sendgrid_api.mail.base' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailBase::class,
                'arguments' => [
                    'mautic.helper.plain_text_message',
                ],
            ],
            'mautic.transport.sendgrid_api.mail.personalization' => [
                'class' => \Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailPersonalization::class,
            ],
            'mautic.transport.sendgrid_api.mail.metadata' => [
                'class' => \Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailMetadata::class,
            ],
            'mautic.transport.sendgrid_api.mail.attachment' => [
                'class' => \Mautic\EmailBundle\Swiftmailer\SendGrid\Mail\SendGridMailAttachment::class,
            ],
            'mautic.transport.sendgrid_api.message' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiMessage::class,
                'arguments' => [
                    'mautic.transport.sendgrid_api.mail.base',
                    'mautic.transport.sendgrid_api.mail.personalization',
                    'mautic.transport.sendgrid_api.mail.metadata',
                    'mautic.transport.sendgrid_api.mail.attachment',
                ],
            ],
            'mautic.transport.sendgrid_api.response' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiResponse::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.transport.sendgrid_api.sendgrid_wrapper' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridWrapper::class,
                'arguments' => [
                    'mautic.transport.sendgrid_api.sendgrid',
                ],
            ],
            'mautic.transport.sendgrid_api.sendgrid' => [
                'class'     => \SendGrid::class,
                'arguments' => [
                    '%mautic.mailer_api_key%',
                ],
            ],
            'mautic.transport.sendgrid_api.calback' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\SendGridApiCallback::class,
                'arguments' => [
                    'mautic.email.model.transport_callback',
                ],
            ],
            'mautic.transport.elasticemail' => [
                'class'        => 'Mautic\EmailBundle\Swiftmailer\Transport\ElasticemailTransport',
                'arguments'    => [
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.email.model.transport_callback',
                ],
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
                    'mautic.email.model.transport_callback',
                ],
            ],
            'mautic.sparkpost.factory' => [
                'class'     => \Mautic\EmailBundle\Swiftmailer\Sparkpost\SparkpostFactory::class,
                'arguments' => [
                    'mautic.guzzle.client',
                ],
            ],
            'mautic.guzzle.client.factory' => [
                'class' => \Mautic\EmailBundle\Swiftmailer\Guzzle\ClientFactory::class,
            ],
            'mautic.guzzle.client' => [
                'class'     => \Http\Adapter\Guzzle6\Client::class,
                'factory'   => ['@mautic.guzzle.client.factory', 'create'],
            ],
            'mautic.helper.mailbox' => [
                'class'     => 'Mautic\EmailBundle\MonitoredEmail\Mailbox',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.paths',
                ],
            ],
            'mautic.message.search.contact' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder::class,
                'arguments' => [
                    'mautic.email.repository.stat',
                    'mautic.lead.repository.lead',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.message.processor.bounce' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\Bounce::class,
                'arguments' => [
                    'swiftmailer.transport.real',
                    'mautic.message.search.contact',
                    'mautic.email.repository.stat',
                    'mautic.lead.model.lead',
                    'translator',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.message.processor.unsubscribe' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe::class,
                'arguments' => [
                    'swiftmailer.transport.real',
                    'mautic.message.search.contact',
                    'mautic.lead.model.lead',
                    'translator',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.message.processor.feedbackloop' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop::class,
                'arguments' => [
                    'mautic.message.search.contact',
                    'mautic.lead.model.lead',
                    'translator',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.message.processor.replier' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\Reply::class,
                'arguments' => [
                    'mautic.email.repository.stat',
                    'mautic.message.search.contact',
                    'mautic.lead.model.lead',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.helper.message' => [
                'class'     => 'Mautic\EmailBundle\Helper\MessageHelper',
                'arguments' => [
                    'mautic.message.processor.bounce',
                    'mautic.message.processor.unsubscribe',
                    'mautic.message.processor.feedbackloop',
                ],
            ],
            'mautic.helper.mailer' => [
                'class'     => \Mautic\EmailBundle\Helper\MailHelper::class,
                'arguments' => [
                    'mautic.factory',
                    'mailer',
                ],
            ],
            'mautic.helper.plain_text_message' => [
                'class'     => \Mautic\EmailBundle\Helper\PlainTextMessageHelper::class,
            ],
            'mautic.validator.email' => [
                'class'     => \Mautic\EmailBundle\Helper\EmailValidator::class,
                'arguments' => [
                    'translator',
                    'event_dispatcher',
                ],
            ],
            'mautic.email.fetcher' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Fetcher::class,
                'arguments' => [
                    'mautic.helper.mailbox',
                    'event_dispatcher',
                    'translator',
                ],
            ],
            'mautic.email.helper.stat' => [
                'class'     => \Mautic\EmailBundle\Stat\StatHelper::class,
                'arguments' => [
                    'mautic.email.repository.stat',
                ],
            ],
            'mautic.email.helper.request.storage' => [
                'class'     => \Mautic\EmailBundle\Helper\RequestStorageHelper::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
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
                    'mautic.email.model.send_email_to_contacts',
                    'mautic.tracker.device',
                    'mautic.page.repository.redirect',
                    'mautic.helper.cache_storage',
                ],
            ],
            'mautic.email.model.send_email_to_user' => [
                'class'     => \Mautic\EmailBundle\Model\SendEmailToUser::class,
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.model.send_email_to_contacts' => [
                'class'     => \Mautic\EmailBundle\Model\SendEmailToContact::class,
                'arguments' => [
                    'mautic.helper.mailer',
                    'mautic.email.helper.stat',
                    'mautic.lead.model.dnc',
                    'translator',
                ],
            ],
            'mautic.email.model.transport_callback' => [
                'class'     => \Mautic\EmailBundle\Model\TransportCallback::class,
                'arguments' => [
                    'mautic.lead.model.dnc',
                    'mautic.message.search.contact',
                    'mautic.email.repository.stat',
                ],
            ],
            'mautic.email.transport_type' => [
                'class'     => \Mautic\EmailBundle\Model\TransportType::class,
                'arguments' => [],
            ],
        ],
        'commands' => [
            'mautic.email.command.fetch' => [
                'class'     => \Mautic\EmailBundle\Command\ProcessFetchEmailCommand::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.email.fetcher',
                ],
                'tag' => 'console.command',
            ],
        ],
        'validator' => [
            'mautic.email.validator.multiple_emails_valid_validator' => [
                'class'     => \Mautic\EmailBundle\Validator\MultipleEmailsValidValidator::class,
                'arguments' => [
                    'mautic.validator.email',
                ],
                'tag' => 'validator.constraint_validator',
            ],
        ],
        'repositories' => [
            'mautic.email.repository.emailReply' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\EmailBundle\Entity\EmailReply::class,
                ],
            ],
            'mautic.email.repository.stat' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\EmailBundle\Entity\Stat::class,
                ],
            ],
        ],
    ],
    'parameters' => [
        'mailer_api_key'               => null, // Api key from mail delivery provider.
        'mailer_from_name'             => 'Mautic',
        'mailer_from_email'            => 'email@yoursite.com',
        'mailer_return_path'           => null,
        'mailer_transport'             => 'mail',
        'mailer_append_tracking_pixel' => true,
        'mailer_convert_embed_images'  => false,
        'mailer_host'                  => '',
        'mailer_port'                  => null,
        'mailer_user'                  => null,
        'mailer_password'              => null,
        'mailer_encryption'            => null, //tls or ssl,
        'mailer_auth_mode'             => null, //plain, login or cram-md5
        'mailer_amazon_region'         => 'email-smtp.us-east-1.amazonaws.com',
        'mailer_custom_headers'        => [],
        'mailer_spool_type'            => 'memory', //memory = immediate; file = queue
        'mailer_spool_path'            => '%kernel.root_dir%/spool',
        'mailer_spool_msg_limit'       => null,
        'mailer_spool_time_limit'      => null,
        'mailer_spool_recover_timeout' => 900,
        'mailer_spool_clear_timeout'   => 1800,
        'unsubscribe_text'             => null,
        'webview_text'                 => null,
        'unsubscribe_message'          => null,
        'resubscribe_message'          => null,
        'monitored_email'              => [
            'general' => [
                'address'         => null,
                'host'            => null,
                'port'            => '993',
                'encryption'      => '/ssl',
                'user'            => null,
                'password'        => null,
                'use_attachments' => false,
            ],
            'EmailBundle_bounces' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => null,
            ],
            'EmailBundle_unsubscribes' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => null,
            ],
            'EmailBundle_replies' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => null,
            ],
        ],
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
