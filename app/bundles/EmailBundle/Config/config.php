<?php

return [
    'routes' => [
        'main' => [
            'mautic_email_index' => [
                'path'       => '/emails/{page}',
                'controller' => 'Mautic\EmailBundle\Controller\EmailController::indexAction',
            ],
            'mautic_email_graph_stats' => [
                'path'       => '/emails-graph-stats/{objectId}/{isVariant}/{dateFrom}/{dateTo}',
                'controller' => 'Mautic\EmailBundle\Controller\EmailGraphStatsController::viewAction',
            ],
            'mautic_email_action' => [
                'path'       => '/emails/{objectAction}/{objectId}',
                'controller' => 'Mautic\EmailBundle\Controller\EmailController::executeAction',
            ],
            'mautic_email_contacts' => [
                'path'       => '/emails/view/{objectId}/contact/{page}',
                'controller' => 'Mautic\EmailBundle\Controller\EmailController::contactsAction',
            ],
        ],
        'api' => [
            'mautic_api_emailstandard' => [
                'standard_entity' => true,
                'name'            => 'emails',
                'path'            => '/emails',
                'controller'      => \Mautic\EmailBundle\Controller\Api\EmailApiController::class,
            ],
            'mautic_api_sendemail' => [
                'path'       => '/emails/{id}/send',
                'controller' => 'Mautic\EmailBundle\Controller\Api\EmailApiController::sendAction',
                'method'     => 'POST',
            ],
            'mautic_api_sendcontactemail' => [
                'path'       => '/emails/{id}/contact/{leadId}/send',
                'controller' => 'Mautic\EmailBundle\Controller\Api\EmailApiController::sendLeadAction',
                'method'     => 'POST',
            ],
            'mautic_api_reply' => [
                'path'       => '/emails/reply/{trackingHash}',
                'controller' => 'Mautic\EmailBundle\Controller\Api\EmailApiController::replyAction',
                'method'     => 'POST',
            ],
        ],
        'public' => [
            'mautic_plugin_tracker' => [
                'path'         => '/plugin/{integration}/tracking.gif',
                'controller'   => 'Mautic\EmailBundle\Controller\PublicController::pluginTrackingGifAction',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_email_tracker' => [
                'path'       => '/email/{idHash}.gif',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::trackingImageAction',
            ],
            'mautic_email_webview' => [
                'path'       => '/email/view/{idHash}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::indexAction',
            ],
            'mautic_email_unsubscribe' => [
                'path'       => '/email/unsubscribe/{idHash}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::unsubscribeAction',
            ],
            'mautic_email_resubscribe' => [
                'path'       => '/email/resubscribe/{idHash}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::resubscribeAction',
            ],
            'mautic_mailer_transport_callback' => [
                'path'       => '/mailer/callback',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::mailerCallbackAction',
            ],
            'mautic_email_preview' => [
                'path'       => '/email/preview/{objectId}',
                'controller' => 'Mautic\EmailBundle\Controller\PublicController::previewAction',
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
        'other' => [
            'mautic.di.env_processor.mailerdsn' => [
                'class' => \Mautic\EmailBundle\DependencyInjection\EnvProcessor\MailerDsnEnvVarProcessor::class,
                'tag'   => 'container.env_var_processor',
            ],
            'mautic.helper.mailbox' => [
                'class'     => \Mautic\EmailBundle\MonitoredEmail\Mailbox::class,
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
                    'mautic.helper.email.address',
                ],
            ],
            'mautic.helper.mailer' => [
                'class'     => \Mautic\EmailBundle\Helper\MailHelper::class,
                'arguments' => [
                    'mautic.factory',
                    'mailer',
                ],
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
        'mailer_from_name'               => 'Mautic',
        'mailer_from_email'              => 'email@yoursite.com',
        'mailer_reply_to_email'          => null,
        'mailer_return_path'             => null,
        'mailer_append_tracking_pixel'   => true,
        'mailer_convert_embed_images'    => false,
        'mailer_custom_headers'          => [],
        'mailer_dsn'                     => 'smtp://localhost:25',
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
        'mailer_is_owner'                                                   => false,
        'default_signature_text'                                            => null,
        'email_frequency_number'                                            => 0,
        'email_frequency_time'                                              => 'DAY',
        'show_contact_preferences'                                          => false,
        'show_contact_frequency'                                            => false,
        'show_contact_pause_dates'                                          => false,
        'show_contact_preferred_channels'                                   => false,
        'show_contact_categories'                                           => false,
        'show_contact_segments'                                             => false,
        'disable_trackable_urls'                                            => false,
        'theme_email_default'                                               => 'blank',
        'mailer_memory_msg_limit'                                           => 100,
        \Mautic\EmailBundle\Form\Type\ConfigType::MINIFY_EMAIL_HTML         => false,
    ],
];
