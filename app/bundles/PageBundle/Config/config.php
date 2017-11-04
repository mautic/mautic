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
                'class'     => 'Mautic\PageBundle\EventListener\PageSubscriber',
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.page.model.page',
                ],
            ],
            'mautic.pagebuilder.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\BuilderSubscriber',
                'arguments' => [
                    'mautic.page.helper.token',
                    'mautic.helper.integration',
                    'mautic.page.model.page',
                ],
            ],
            'mautic.pagetoken.subscriber' => [
                'class' => 'Mautic\PageBundle\EventListener\TokenSubscriber',
            ],
            'mautic.page.pointbundle.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                ],

            ],
            'mautic.page.reportbundle.subscriber' => [
                'class' => 'Mautic\PageBundle\EventListener\ReportSubscriber',
            ],
            'mautic.page.campaignbundle.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.page.model.page',
                    'mautic.campaign.model.event',
                    'mautic.lead.model.lead',
                    'mautic.page.helper.tracking',
                ],
            ],
            'mautic.page.leadbundle.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.page.model.page',
                    'mautic.page.model.video',
                ],
                'methodCalls' => [
                    'setModelFactory' => ['mautic.model.factory'],
                ],
            ],
            'mautic.page.calendarbundle.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\CalendarSubscriber',
                'arguments' => [
                    'mautic.page.model.page',
                ],
            ],
            'mautic.page.configbundle.subscriber' => [
                'class' => 'Mautic\PageBundle\EventListener\ConfigSubscriber',
            ],
            'mautic.page.search.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.helper.user',
                    'mautic.page.model.page',
                ],
            ],
            'mautic.page.webhook.subscriber' => [
                'class'       => 'Mautic\PageBundle\EventListener\WebhookSubscriber',
                'methodCalls' => [
                    'setWebhookModel' => ['mautic.webhook.model.webhook'],
                ],
            ],
            'mautic.page.dashboard.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.page.model.page',
                ],
            ],
            'mautic.page.js.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\BuildJsSubscriber',
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.page.helper.tracking',
                ],
            ],
            'mautic.page.maintenance.subscriber' => [
                'class'     => 'Mautic\PageBundle\EventListener\MaintenanceSubscriber',
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.page.stats.subscriber' => [
                'class'     => \Mautic\PageBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.page' => [
                'class'     => 'Mautic\PageBundle\Form\Type\PageType',
                'arguments' => 'mautic.factory',
                'alias'     => 'page',
            ],
            'mautic.form.type.pagevariant' => [
                'class'     => 'Mautic\PageBundle\Form\Type\VariantType',
                'arguments' => 'mautic.factory',
                'alias'     => 'pagevariant',
            ],
            'mautic.form.type.pointaction_pointhit' => [
                'class' => 'Mautic\PageBundle\Form\Type\PointActionPageHitType',
                'alias' => 'pointaction_pagehit',
            ],
            'mautic.form.type.pointaction_urlhit' => [
                'class' => 'Mautic\PageBundle\Form\Type\PointActionUrlHitType',
                'alias' => 'pointaction_urlhit',
            ],
            'mautic.form.type.pagehit.campaign_trigger' => [
                'class' => 'Mautic\PageBundle\Form\Type\CampaignEventPageHitType',
                'alias' => 'campaignevent_pagehit',
            ],
            'mautic.form.type.pagelist' => [
                'class'     => 'Mautic\PageBundle\Form\Type\PageListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'page_list',
            ],
            'mautic.form.type.preferencecenterlist' => [
                'class'     => 'Mautic\PageBundle\Form\Type\PreferenceCenterListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'preference_center_list',
            ],
            'mautic.form.type.page_abtest_settings' => [
                'class' => 'Mautic\PageBundle\Form\Type\AbTestPropertiesType',
                'alias' => 'page_abtest_settings',
            ],
            'mautic.form.type.page_publish_dates' => [
                'class' => 'Mautic\PageBundle\Form\Type\PagePublishDatesType',
                'alias' => 'page_publish_dates',
            ],
            'mautic.form.type.pageconfig' => [
                'class' => 'Mautic\PageBundle\Form\Type\ConfigType',
                'alias' => 'pageconfig',
            ],
            'mautic.form.type.trackingconfig' => [
                'class' => 'Mautic\PageBundle\Form\Type\ConfigTrackingPageType',
                'alias' => 'trackingconfig',
            ],
            'mautic.form.type.slideshow_config' => [
                'class' => 'Mautic\PageBundle\Form\Type\SlideshowGlobalConfigType',
                'alias' => 'slideshow_config',
            ],
            'mautic.form.type.slideshow_slide_config' => [
                'class' => 'Mautic\PageBundle\Form\Type\SlideshowSlideConfigType',
                'alias' => 'slideshow_slide_config',
            ],
            'mautic.form.type.redirect_list' => [
                'class'     => 'Mautic\PageBundle\Form\Type\RedirectListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'redirect_list',
            ],
            'mautic.form.type.page_dashboard_hits_in_time_widget' => [
                'class' => 'Mautic\PageBundle\Form\Type\DashboardHitsInTimeWidgetType',
                'alias' => 'page_dashboard_hits_in_time_widget',
            ],
            'mautic.page.tracking.pixel.send' => [
                'class'     => 'Mautic\PageBundle\Form\Type\TrackingPixelSendType',
                'alias'     => 'tracking_pixel_send_action',
                'arguments' => [
                    'mautic.page.helper.tracking',
                ],
            ],
        ],
        'models' => [
            'mautic.page.model.page' => [
                'class'     => 'Mautic\PageBundle\Model\PageModel',
                'arguments' => [
                    'mautic.helper.cookie',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.field',
                    'mautic.page.model.redirect',
                    'mautic.page.model.trackable',
                    'mautic.queue.service',
                ],
                'methodCalls' => [
                    'setCatInUrl' => [
                        '%mautic.cat_in_page_url%',
                    ],
                    'setTrackByFingerprint' => [
                        '%mautic.track_by_fingerprint%',
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
                'class'     => 'Mautic\PageBundle\Model\TrackableModel',
                'arguments' => [
                    'mautic.page.model.redirect',
                ],
            ],
            'mautic.page.model.video' => [
                'class'     => 'Mautic\PageBundle\Model\VideoModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.helper.ip_lookup',
                ],
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
                    'mautic.lead.model.lead',
                    'session',
                    'mautic.helper.core_parameters',
                    'request_stack',
                ],
            ],
        ],
    ],

    'parameters' => [
        'cat_in_page_url'       => false,
        'google_analytics'      => false,
        'track_contact_by_ip'   => false,
        'track_by_fingerprint'  => false,
        'track_by_tracking_url' => true,
        'redirect_list_types'   => [
            '301' => 'mautic.page.form.redirecttype.permanent',
            '302' => 'mautic.page.form.redirecttype.temporary',
        ],
        'google_analytics_id'                   => null,
        'google_analytics_trackingpage_enabled' => false,
        'google_analytics_landingpage_enabled'  => false,
        'facebook_pixel_id'                     => null,
        'facebook_pixel_trackingpage_enabled'   => false,
        'facebook_pixel_landingpage_enabled'    => false,
    ],
];
