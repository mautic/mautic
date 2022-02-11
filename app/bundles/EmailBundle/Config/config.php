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
            'mautic_api_reply' => [
                'path'       => '/emails/reply/{trackingHash}',
                'controller' => 'MauticEmailBundle:Api\EmailApi:reply',
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
            'mautic.email.subscriber.aggregate_stats' => [
                'class'     => \Mautic\EmailBundle\EventListener\GraphAggregateStatsSubscriber::class,
                'arguments' => [
                    'mautic.email.helper.stats_collection',
                ],
            ],
            'mautic.email.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.email.model.email',
                    'translator',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.email.queue.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\QueueSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
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
                'class'     => \Mautic\EmailBundle\EventListener\BuilderSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.email.model.email',
                    'mautic.page.model.trackable',
                    'mautic.page.model.redirect',
                    'translator',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.emailtoken.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\TokenSubscriber::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.lead.helper.primary_company',
                ],
            ],
            'mautic.email.generated_columns.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\GeneratedColumnSubscriber::class,
            ],
            'mautic.email.campaignbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'mautic.campaign.executioner.realtime',
                    'mautic.email.model.send_email_to_user',
                    'translator',
                ],
            ],
            'mautic.email.campaignbundle.condition_subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\CampaignConditionSubscriber::class,
                'arguments' => [
                    'mautic.validator.email',
                ],
            ],
            'mautic.email.formbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'mautic.tracker.contact',
                ],
            ],
            'mautic.email.reportbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                    'mautic.lead.model.company_report_data',
                    'mautic.email.repository.stat',
                    'mautic.generated.columns.provider',
                ],
            ],
            'mautic.email.leadbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.email.repository.emailReply',
                    'mautic.email.repository.stat',
                    'translator',
                    'router',
                ],
            ],
            'mautic.email.pointbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\PointSubscriber::class,
                'arguments' => [
                    'mautic.point.model.point',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.email.touser.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\EmailToUserSubscriber::class,
                'arguments' => [
                    'mautic.email.model.send_email_to_user',
                ],
            ],
            'mautic.email.search.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.email.model.email',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.email.webhook.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\WebhookSubscriber::class,
                'arguments' => [
                    'mautic.webhook.model.webhook',
                ],
            ],
            'mautic.email.configbundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\ConfigSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.email.pagebundle.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\PageSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'mautic.campaign.executioner.realtime',
                    'request_stack',
                ],
            ],
            'mautic.email.dashboard.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\DashboardSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'router',
                ],
            ],
            'mautic.email.dashboard.best.hours.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\DashboardBestHoursSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                ],
            ],
            'mautic.email.broadcast.subscriber' => [
                'class'     => \Mautic\EmailBundle\EventListener\BroadcastSubscriber::class,
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
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.email.subscriber.contact_tracker' => [
                'class'     => \Mautic\EmailBundle\EventListener\TrackingSubscriber::class,
                'arguments' => [
                    'mautic.email.repository.stat',
                ],
            ],
            'mautic.email.subscriber.determine_winner' => [
                'class'     => \Mautic\EmailBundle\EventListener\DetermineWinnerSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'translator',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.email' => [
                'class'     => \Mautic\EmailBundle\Form\Type\EmailType::class,
                'arguments' => [
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.stage.model.stage',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.form.type.email.utm_tags' => [
                'class' => \Mautic\EmailBundle\Form\Type\EmailUtmTagsType::class,
            ],
            'mautic.form.type.emailvariant' => [
                'class'     => \Mautic\EmailBundle\Form\Type\VariantType::class,
                'arguments' => ['mautic.email.model.email'],
            ],
            'mautic.form.type.email_list' => [
                'class' => \Mautic\EmailBundle\Form\Type\EmailListType::class,
            ],
            'mautic.form.type.email_click_decision' => [
                'class' => \Mautic\EmailBundle\Form\Type\EmailClickDecisionType::class,
            ],
            'mautic.form.type.emailopen_list' => [
                'class' => \Mautic\EmailBundle\Form\Type\EmailOpenType::class,
            ],
            'mautic.form.type.emailsend_list' => [
                'class'     => \Mautic\EmailBundle\Form\Type\EmailSendType::class,
                'arguments' => ['router'],
            ],
            'mautic.form.type.formsubmit_sendemail_admin' => [
                'class' => \Mautic\EmailBundle\Form\Type\FormSubmitActionUserEmailType::class,
            ],
            'mautic.email.type.email_abtest_settings' => [
                'class' => \Mautic\EmailBundle\Form\Type\AbTestPropertiesType::class,
            ],
            'mautic.email.type.batch_send' => [
                'class' => \Mautic\EmailBundle\Form\Type\BatchSendType::class,
            ],
            'mautic.form.type.emailconfig' => [
                'class'     => \Mautic\EmailBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'translator',
                    'mautic.email.transport_type',
                ],
            ],
            'mautic.form.type.coreconfig_monitored_mailboxes' => [
                'class'     => \Mautic\EmailBundle\Form\Type\ConfigMonitoredMailboxesType::class,
                'arguments' => [
                    'mautic.helper.mailbox',
                ],
            ],
            'mautic.form.type.coreconfig_monitored_email' => [
                'class'     => \Mautic\EmailBundle\Form\Type\ConfigMonitoredEmailType::class,
                'arguments' => 'event_dispatcher',
            ],
            'mautic.form.type.email_dashboard_emails_in_time_widget' => [
                'class'     => \Mautic\EmailBundle\Form\Type\DashboardEmailsInTimeWidgetType::class,
            ],
            'mautic.form.type.email_dashboard_sent_email_to_contacts_widget' => [
                'class'     => \Mautic\EmailBundle\Form\Type\DashboardSentEmailToContactsWidgetType::class,
            ],
            'mautic.form.type.email_dashboard_most_hit_email_redirects_widget' => [
                'class'     => \Mautic\EmailBundle\Form\Type\DashboardMostHitEmailRedirectsWidgetType::class,
            ],
            'mautic.form.type.email_to_user' => [
                'class' => Mautic\EmailBundle\Form\Type\EmailToUserType::class,
            ],
        ],
        'other' => [
            'mautic.guzzle.client.factory' => [
                'class' => \Mautic\EmailBundle\Mailer\Guzzle\ClientFactory::class,
            ],
            /**
             * Needed for Sparkpost integration. Can be removed when this integration is moved to
             * its own plugin.
             */
            'mautic.guzzle.client' => [
                'class'     => \Http\Adapter\Guzzle7\Client::class,
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
                    'mailer.default_transport',
                    'mautic.message.search.contact',
                    'mautic.email.repository.stat',
                    'mautic.lead.model.lead',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.message.processor.unsubscribe' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe::class,
                'arguments' => [
                    'mailer.default_transport',
                    'mautic.message.search.contact',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.message.processor.feedbackloop' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop::class,
                'arguments' => [
                    'mautic.message.search.contact',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.lead.model.dnc',
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
                    'mautic.tracker.contact',
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
            'mautic.email.helper.stats_collection' => [
                'class'     => \Mautic\EmailBundle\Helper\StatsCollectionHelper::class,
                'arguments' => [
                    'mautic.email.stats.helper_container',
                ],
            ],
            'mautic.email.stats.helper_container' => [
                'class' => \Mautic\EmailBundle\Stats\StatHelperContainer::class,
            ],
            'mautic.email.stats.helper_bounced' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\BouncedHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.stats.helper_clicked' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\ClickedHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.stats.helper_failed' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\FailedHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.stats.helper_opened' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\OpenedHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.stats.helper_sent' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\SentHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.stats.helper_unsubscribed' => [
                'class'     => \Mautic\EmailBundle\Stats\Helper\UnsubscribedHelper::class,
                'arguments' => [
                    'mautic.stats.aggregate.collector',
                    'doctrine.dbal.default_connection',
                    'mautic.generated.columns.provider',
                    'mautic.helper.user',
                ],
                'tag' => 'mautic.email_stat_helper',
            ],
            'mautic.email.mailer.factory' => [
                'class'     => \Mautic\EmailBundle\Mailer\MailerFactory::class,
                'arguments' => [
                    'mailer',
                ],
            ],
            'mautic.email.mailer.email_sender' => [
                'class'     => \Mautic\EmailBundle\Mailer\EmailSender::class,
                'arguments' => [
                    'mailer',
                    'translator',
                    '%mautic.mailer_from_email%',
                    '%mautic.mailer_from_name%',
                ],
            ],
        ],
        'models' => [
            'mautic.email.model.email' => [
                'class'     => \Mautic\EmailBundle\Model\EmailModel::class,
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
                    'mautic.tracker.contact',
                    'mautic.lead.model.dnc',
                    'mautic.email.helper.stats_collection',
                    'mautic.security',
                ],
            ],
            'mautic.email.model.send_email_to_user' => [
                'class'     => \Mautic\EmailBundle\Model\SendEmailToUser::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'event_dispatcher',
                    'mautic.lead.validator.custom_field',
                    'mautic.validator.email',
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
//            'mautic.email.command.fetch' => [
//                'class'     => \Mautic\EmailBundle\Command\ProcessFetchEmailCommand::class,
//                'arguments' => [
//                    'mautic.helper.core_parameters',
//                    'mautic.email.fetcher',
//                ],
//                'tag' => 'console.command',
//            ],
        ],
        'validator' => [
            'mautic.email.validator.multiple_emails_valid_validator' => [
                'class'     => \Mautic\EmailBundle\Validator\MultipleEmailsValidValidator::class,
                'arguments' => [
                    'mautic.validator.email',
                ],
                'tag' => 'validator.constraint_validator',
            ],
            'mautic.email.validator.email_or_token_list_validator' => [
                'class'     => \Mautic\EmailBundle\Validator\EmailOrEmailTokenListValidator::class,
                'arguments' => [
                    'mautic.validator.email',
                    'mautic.lead.validator.custom_field',
                ],
                'tag' => 'validator.constraint_validator',
            ],
        ],
        'repositories' => [
            'mautic.email.repository.email' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\EmailBundle\Entity\Email::class,
                ],
            ],
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
        'fixtures' => [
            'mautic.email.fixture.email' => [
                'class'     => Mautic\EmailBundle\DataFixtures\ORM\LoadEmailData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.email.model.email'],
            ],
        ],
    ],
    'parameters' => [
        'mailer_api_key'                 => null, // Api key from mail delivery provider.
        'mailer_from_name'               => 'Mautic',
        'mailer_from_email'              => 'email@yoursite.com',
        'mailer_reply_to_email'          => null,
        'mailer_return_path'             => null,
        'mailer_transport'               => 'smtp',
        'mailer_append_tracking_pixel'   => true,
        'mailer_convert_embed_images'    => false,
        'mailer_host'                    => '',
        'mailer_port'                    => null,
        'mailer_user'                    => null,
        'mailer_password'                => null,
        'mailer_encryption'              => null, //tls or ssl,
        'mailer_auth_mode'               => null, //plain, login or cram-md5
        'mailer_amazon_region'           => 'us-east-1',
        'mailer_amazon_other_region'     => null,
        'mailer_custom_headers'          => [],
        'mailer_spool_type'              => 'memory', //memory = immediate; file = queue
        'mailer_spool_path'              => '%kernel.root_dir%/../var/spool',
        'mailer_spool_msg_limit'         => null,
        'mailer_spool_time_limit'        => null,
        'mailer_spool_recover_timeout'   => 900,
        'mailer_dsn'                     => 'null://null',
        'mailer_messenger_dsn'           => 'sync://',
        'unsubscribe_text'               => null,
        'webview_text'                   => null,
        'unsubscribe_message'            => null,
        'resubscribe_message'            => null,
        'monitored_email'                => [
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
        'email_frequency_number'              => 0,
        'email_frequency_time'                => 'DAY',
        'show_contact_preferences'            => false,
        'show_contact_frequency'              => false,
        'show_contact_pause_dates'            => false,
        'show_contact_preferred_channels'     => false,
        'show_contact_categories'             => false,
        'show_contact_segments'               => false,
        'mailer_mailjet_sandbox'              => false,
        'mailer_mailjet_sandbox_default_mail' => null,
        'disable_trackable_urls'              => false,
        'theme_email_default'                 => 'blank',
    ],
];
