<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'     => array(
        'main'   => array(
            'mautic_page_buildertoken_index' => array(
                'path'       => '/pages/buildertokens/{page}',
                'controller' => 'MauticPageBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_page_index'              => array(
                'path'       => '/pages/{page}',
                'controller' => 'MauticPageBundle:Page:index'
            ),
            'mautic_page_action'             => array(
                'path'       => '/pages/{objectAction}/{objectId}',
                'controller' => 'MauticPageBundle:Page:execute'
            ),
        ),
        'public' => array(
            'mautic_page_tracker'  => array(
                'path'       => '/mtracking.gif',
                'controller' => 'MauticPageBundle:Public:trackingImage'
            ),
            'mautic_url_redirect' => array(
                'path'       => '/r/{redirectId}',
                'controller' => 'MauticPageBundle:Public:redirect'
            ),
            // @deprecated; to be removed in 2.0 use mautic_url_redirect instead
            'mautic_page_trackable' => array(
                'path'       => '/r/{redirectId}',
                'controller' => 'MauticPageBundle:Public:redirect'
            ),
            'mautic_page_redirect' => array(
                'path'       => '/redirect/{redirectId}',
                'controller' => 'MauticPageBundle:Public:redirect'
            ),
            'mautic_page_preview' => array(
                'path'       => '/page/preview/{id}',
                'controller' => 'MauticPageBundle:Public:preview'
            )
        ),
        'api'    => array(
            'mautic_api_getpages' => array(
                'path'       => '/pages',
                'controller' => 'MauticPageBundle:Api\PageApi:getEntities',
            ),
            'mautic_api_getpage'  => array(
                'path'       => '/pages/{id}',
                'controller' => 'MauticPageBundle:Api\PageApi:getEntity',
            )
        ),
        'catchall'  => array(
            'mautic_page_public'   => array(
                'path'       => '/{slug}',
                'controller' => 'MauticPageBundle:Public:index',
                'requirements' => array(
                    // @deprecated support for /addons; to be removed in 2.0
                    'slug' => '^(?!(_(profiler|wdt)|css|images|js|favicon.ico|apps/bundles/|plugins/|addons/)).+'
                )
            ),
        )
    ),

    'menu' => array(
        'main' => array(
            'items'    => array(
                'mautic.page.pages' => array(
                    'route' => 'mautic_page_index',
                    'access'    => array('page:pages:viewown', 'page:pages:viewother'),
                    'parent'    => 'mautic.core.components',
                    'priority'  => 100
                )
            )
        )
    ),

    'categories' => array(
        'page' => null
    ),

    'services'   => array(
        'events' => array(
            'mautic.page.subscriber'                => array(
                'class' => 'Mautic\PageBundle\EventListener\PageSubscriber'
            ),
            'mautic.pagebuilder.subscriber'         => array(
                'class' => 'Mautic\PageBundle\EventListener\BuilderSubscriber'
            ),
            'mautic.pagetoken.subscriber'           => array(
                'class' => 'Mautic\PageBundle\EventListener\TokenSubscriber'
            ),
            'mautic.page.pointbundle.subscriber'    => array(
                'class' => 'Mautic\PageBundle\EventListener\PointSubscriber'
            ),
            'mautic.page.reportbundle.subscriber'   => array(
                'class' => 'Mautic\PageBundle\EventListener\ReportSubscriber'
            ),
            'mautic.page.campaignbundle.subscriber' => array(
                'class' => 'Mautic\PageBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.page.leadbundle.subscriber'     => array(
                'class' => 'Mautic\PageBundle\EventListener\LeadSubscriber'
            ),
            'mautic.page.calendarbundle.subscriber' => array(
                'class' => 'Mautic\PageBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.page.configbundle.subscriber'   => array(
                'class' => 'Mautic\PageBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.page.search.subscriber'         => array(
                'class' => 'Mautic\PageBundle\EventListener\SearchSubscriber'
            ),
            'mautic.page.webhook.subscriber'        => array(
                'class' => 'Mautic\PageBundle\EventListener\WebhookSubscriber'
            ),
            'mautic.page.dashboard.subscriber'      => array(
                'class' => 'Mautic\PageBundle\EventListener\DashboardSubscriber'
            ),
            'mautic.page.js.subscriber'           => array(
                'class' => 'Mautic\PageBundle\EventListener\BuildJsSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.page'                     => array(
                'class'     => 'Mautic\PageBundle\Form\Type\PageType',
                'arguments' => 'mautic.factory',
                'alias'     => 'page'
            ),
            'mautic.form.type.pagevariant'              => array(
                'class'     => 'Mautic\PageBundle\Form\Type\VariantType',
                'arguments' => 'mautic.factory',
                'alias'     => 'pagevariant'
            ),
            'mautic.form.type.pointaction_pointhit'     => array(
                'class' => 'Mautic\PageBundle\Form\Type\PointActionPageHitType',
                'alias' => 'pointaction_pagehit'
            ),
            'mautic.form.type.pointaction_urlhit'       => array(
                'class' => 'Mautic\PageBundle\Form\Type\PointActionUrlHitType',
                'alias' => 'pointaction_urlhit'
            ),
            'mautic.form.type.pagehit.campaign_trigger' => array(
                'class' => 'Mautic\PageBundle\Form\Type\CampaignEventPageHitType',
                'alias' => 'campaignevent_pagehit'
            ),
            'mautic.form.type.pagelist'                 => array(
                'class'     => 'Mautic\PageBundle\Form\Type\PageListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'page_list',
            ),
            'mautic.form.type.page_abtest_settings'     => array(
                'class' => 'Mautic\PageBundle\Form\Type\AbTestPropertiesType',
                'alias' => 'page_abtest_settings'
            ),
            'mautic.form.type.page_publish_dates'       => array(
                'class' => 'Mautic\PageBundle\Form\Type\PagePublishDatesType',
                'alias' => 'page_publish_dates'
            ),
            'mautic.form.type.pageconfig'               => array(
                'class' => 'Mautic\PageBundle\Form\Type\ConfigType',
                'alias' => 'pageconfig'
            ),
            'mautic.form.type.slideshow_config'         => array(
                'class' => 'Mautic\PageBundle\Form\Type\SlideshowGlobalConfigType',
                'alias' => 'slideshow_config'
            ),
            'mautic.form.type.slideshow_slide_config'   => array(
                'class' => 'Mautic\PageBundle\Form\Type\SlideshowSlideConfigType',
                'alias' => 'slideshow_slide_config'
            ),
            'mautic.form.type.redirect_list'            => array(
                'class' => 'Mautic\PageBundle\Form\Type\RedirectListType',
                'arguments' => 'mautic.factory',
                'alias' => 'redirect_list'
            ),
            'mautic.form.type.page_dashboard_hits_in_time_widget' => array(
                'class' => 'Mautic\PageBundle\Form\Type\DashboardHitsInTimeWidgetType',
                'alias' => 'page_dashboard_hits_in_time_widget'
            )
        )
    ),

    'parameters' => array(
        'cat_in_page_url'  => false,
        'google_analytics' => false,

        'redirect_list_types' => array(
            '301' => 'mautic.page.form.redirecttype.permanent',
            '302' => 'mautic.page.form.redirecttype.temporary'
        )
    )
);
