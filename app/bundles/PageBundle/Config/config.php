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
            'mautic_page_index' => [
                'path'       => '/pages/{page}',
                'controller' => 'MauticPageBundle:Page:index',
            ],
            'mautic_page_action' => [
                'path'       => '/pages/{objectAction}/{objectId}',
                'controller' => 'MauticPageBundle:Page:execute',
            ],
        ],
        'public' => [
            'mautic_page_tracker' => [
                'path'       => '/mtracking.gif',
                'controller' => 'MauticPageBundle:Public:trackingImage',
            ],
            'mautic_page_tracker_cors' => [
                'path'       => '/mtc/event',
                'controller' => 'MauticPageBundle:Public:tracking',
            ],
            'mautic_page_tracker_getcontact' => [
                'path'       => '/mtc',
                'controller' => 'MauticPageBundle:Public:getContactId',
            ],
            'mautic_url_redirect' => [
                'path'       => '/r/{redirectId}',
                'controller' => 'MauticPageBundle:Public:redirect',
            ],
            'mautic_page_redirect' => [
                'path'       => '/redirect/{redirectId}',
                'controller' => 'MauticPageBundle:Public:redirect',
            ],
            'mautic_page_preview' => [
                'path'       => '/page/preview/{id}',
                'controller' => 'MauticPageBundle:Public:preview',
            ],
            'mautic_gated_video_hit' => [
                'path'       => '/video/hit',
                'controller' => 'MauticPageBundle:Public:hitVideo',
            ],
        ],
        'api' => [
            'mautic_api_pagesstandard' => [
                'standard_entity' => true,
                'name'            => 'pages',
                'path'            => '/pages',
                'controller'      => 'MauticPageBundle:Api\PageApi',
            ],
        ],
        'catchall' => [
            'mautic_page_public' => [
                'path'         => '/{slug}',
                'controller'   => 'MauticPageBundle:Public:index',
                'requirements' => [
                    'slug' => '^(?!(_(profiler|wdt)|css|images|js|favicon.ico|apps/bundles/|plugins/)).+',
                ],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'items' => [
                'mautic.page.pages' => [
                    'route'    => 'mautic_page_index',
                    'access'   => ['page:pages:viewown', 'page:pages:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 100,
                ],
            ],
        ],
    ],

    'categories' => [
        'page' => null,
    ],

    'services' => [
        'events' => [
            'mautic.page.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\PageSubscriber::class,
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.page.model.page',
                    'monolog.logger.mautic',
                    'mautic.page.repository.hit',
                    'mautic.page.repository.page',
                    'mautic.page.repository.redirect',
                    'mautic.lead.repository.lead',
                ],
            ],
            'mautic.pagebuilder.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\BuilderSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'mautic.page.helper.token',
                    'mautic.helper.integration',
                    'mautic.page.model.page',
                    'mautic.helper.token_builder.factory',
                    'translator',
                    'doctrine.dbal.default_connection',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.pagetoken.subscriber' => [
                'class' => \Mautic\PageBundle\EventListener\TokenSubscriber::class,
            ],
            'mautic.page.pointbundle.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\PointSubscriber::class,
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.page.reportbundle.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.company_report_data',
                    'mautic.page.repository.hit',
                    'translator',
                ],
            ],
            'mautic.page.campaignbundle.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.page.helper.tracking',
                    'mautic.campaign.executioner.realtime',
                ],
            ],
            'mautic.page.leadbundle.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.page.model.page',
                    'mautic.page.model.video',
                    'translator',
                    'router',
                ],
                'methodCalls' => [
                    'setModelFactory' => ['mautic.model.factory'],
                ],
            ],
            'mautic.page.calendarbundle.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\CalendarSubscriber::class,
                'arguments' => [
                    'mautic.page.model.page',
                    'doctrine.dbal.default_connection',
                    'mautic.security',
                    'translator',
                    'router',
                ],
            ],
            'mautic.page.configbundle.subscriber' => [
                'class' => \Mautic\PageBundle\EventListener\ConfigSubscriber::class,
            ],
            'mautic.page.search.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\SearchSubscriber::class,
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.page.model.page',
                    'mautic.security',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.page.webhook.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\WebhookSubscriber::class,
                'arguments' => [
                    'mautic.webhook.model.webhook',
                ],
            ],
            'mautic.page.dashboard.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\DashboardSubscriber::class,
                'arguments' => [
                    'mautic.page.model.page',
                    'router',
                ],
            ],
            'mautic.page.js.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\BuildJsSubscriber::class,
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.page.helper.tracking',
                    'router',
                ],
            ],
            'mautic.page.maintenance.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\MaintenanceSubscriber::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                    'translator',
                ],
            ],
            'mautic.page.stats.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.page.subscriber.determine_winner' => [
                'class'     => \Mautic\PageBundle\EventListener\DetermineWinnerSubscriber::class,
                'arguments' => [
                    'mautic.page.repository.hit',
                    'translator',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.page' => [
                'class'     => \Mautic\PageBundle\Form\Type\PageType::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.page.model.page',
                    'mautic.security',
                    'mautic.helper.user',
                    'mautic.helper.theme',
                ],
            ],
            'mautic.form.type.pagevariant' => [
                'class'     => \Mautic\PageBundle\Form\Type\VariantType::class,
                'arguments' => ['mautic.page.model.page'],
            ],
            'mautic.form.type.pointaction_pointhit' => [
                'class' => \Mautic\PageBundle\Form\Type\PointActionPageHitType::class,
            ],
            'mautic.form.type.pointaction_urlhit' => [
                'class' => \Mautic\PageBundle\Form\Type\PointActionUrlHitType::class,
            ],
            'mautic.form.type.pagehit.campaign_trigger' => [
                'class' => \Mautic\PageBundle\Form\Type\CampaignEventPageHitType::class,
            ],
            'mautic.form.type.pagelist' => [
                'class'     => \Mautic\PageBundle\Form\Type\PageListType::class,
                'arguments' => [
                    'mautic.page.model.page',
                    'mautic.security',
                ],
            ],
            'mautic.form.type.preferencecenterlist' => [
                'class'     => \Mautic\PageBundle\Form\Type\PreferenceCenterListType::class,
                'arguments' => [
                    'mautic.page.model.page',
                    'mautic.security',
                ],
            ],
            'mautic.form.type.page_abtest_settings' => [
                'class' => \Mautic\PageBundle\Form\Type\AbTestPropertiesType::class,
            ],
            'mautic.form.type.page_publish_dates' => [
                'class' => \Mautic\PageBundle\Form\Type\PagePublishDatesType::class,
            ],
            'mautic.form.type.pageconfig' => [
                'class' => \Mautic\PageBundle\Form\Type\ConfigType::class,
            ],
            'mautic.form.type.trackingconfig' => [
                'class' => \Mautic\PageBundle\Form\Type\ConfigTrackingPageType::class,
            ],
            'mautic.form.type.redirect_list' => [
                'class'     => \Mautic\PageBundle\Form\Type\RedirectListType::class,
                'arguments' => ['mautic.helper.core_parameters'],
            ],
            'mautic.form.type.page_dashboard_hits_in_time_widget' => [
                'class' => \Mautic\PageBundle\Form\Type\DashboardHitsInTimeWidgetType::class,
            ],
            'mautic.page.tracking.pixel.send' => [
                'class'     => \Mautic\PageBundle\Form\Type\TrackingPixelSendType::class,
                'arguments' => [
                    'mautic.page.helper.tracking',
                ],
            ],
        ],
        'models' => [
            'mautic.page.model.page' => [
                'class'     => \Mautic\PageBundle\Model\PageModel::class,
                'arguments' => [
                    'mautic.helper.cookie',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field',
                    'mautic.page.model.redirect',
                    'mautic.page.model.trackable',
                    'mautic.queue.service',
                    'mautic.lead.model.company',
                    'mautic.tracker.device',
                    'mautic.tracker.contact',
                    'mautic.helper.core_parameters',
                ],
                'methodCalls' => [
                    'setCatInUrl' => [
                        '%mautic.cat_in_page_url%',
                    ],
                ],
            ],
            'mautic.page.model.redirect' => [
                'class'     => 'Mautic\PageBundle\Model\RedirectModel',
                'arguments' => [
                    'mautic.helper.url',
                ],
            ],
            'mautic.page.model.trackable' => [
                'class'     => \Mautic\PageBundle\Model\TrackableModel::class,
                'arguments' => [
                    'mautic.page.model.redirect',
                    'mautic.lead.repository.field',
                ],
            ],
            'mautic.page.model.video' => [
                'class'     => 'Mautic\PageBundle\Model\VideoModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.tracker.contact',
                ],
            ],
        ],
        'repositories' => [
            'mautic.page.repository.hit' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PageBundle\Entity\Hit::class,
                ],
            ],
            'mautic.page.repository.page' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PageBundle\Entity\Page::class,
                ],
            ],
            'mautic.page.repository.redirect' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PageBundle\Entity\Redirect::class,
                ],
            ],
        ],
        'fixtures' => [
            'mautic.page.fixture.page_category' => [
                'class'     => \Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.category.model.category'],
            ],
            'mautic.page.fixture.page' => [
                'class'     => \Mautic\PageBundle\DataFixtures\ORM\LoadPageData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page'],
            ],
            'mautic.page.fixture.page_hit' => [
                'class'     => \Mautic\PageBundle\DataFixtures\ORM\LoadPageHitData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page'],
            ],
        ],
        'other' => [
            'mautic.page.helper.token' => [
                'class'     => 'Mautic\PageBundle\Helper\TokenHelper',
                'arguments' => 'mautic.page.model.page',
            ],
            'mautic.page.helper.tracking' => [
                'class'     => 'Mautic\PageBundle\Helper\TrackingHelper',
                'arguments' => [
                    'session',
                    'mautic.helper.core_parameters',
                    'request_stack',
                    'mautic.tracker.contact',
                ],
            ],
        ],
    ],

    'parameters' => [
        'cat_in_page_url'       => false,
        'google_analytics'      => null,
        'track_contact_by_ip'   => false,
        'track_by_tracking_url' => false,
        'redirect_list_types'   => [
            '301' => 'mautic.page.form.redirecttype.permanent',
            '302' => 'mautic.page.form.redirecttype.temporary',
        ],
        'google_analytics_id'                   => null,
        'google_analytics_trackingpage_enabled' => false,
        'google_analytics_landingpage_enabled'  => false,
        'google_analytics_anonymize_ip'         => false,
        'facebook_pixel_id'                     => null,
        'facebook_pixel_trackingpage_enabled'   => false,
        'facebook_pixel_landingpage_enabled'    => false,
    ],
];
