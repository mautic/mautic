<?php

return [
    'routes' => [
        'main' => [
            'mautic_page_index' => [
                'path'       => '/pages/{page}',
                'controller' => 'Mautic\PageBundle\Controller\PageController::indexAction',
            ],
            'mautic_page_action' => [
                'path'       => '/pages/{objectAction}/{objectId}',
                'controller' => 'Mautic\PageBundle\Controller\PageController::executeAction',
            ],
            'mautic_page_results' => [
                'path'       => '/pages/results/{objectId}/{page}',
                'controller' => 'Mautic\PageBundle\Controller\PageController::resultsAction',
            ],
            'mautic_page_export' => [
                'path'       => '/pages/results/{objectId}/export/{format}',
                'controller' => 'Mautic\PageBundle\Controller\PageController::exportAction',
                'defaults'   => [
                    'format' => 'csv',
                ],
            ],
        ],
        'public' => [
            'mautic_page_tracker' => [
                'path'       => '/mtracking.gif',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::trackingImageAction',
            ],
            'mautic_page_tracker_cors' => [
                'path'       => '/mtc/event',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::trackingAction',
            ],
            'mautic_page_tracker_getcontact' => [
                'path'       => '/mtc',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::getContactIdAction',
            ],
            'mautic_url_redirect' => [
                'path'       => '/r/{redirectId}',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::redirectAction',
            ],
            'mautic_page_redirect' => [
                'path'       => '/redirect/{redirectId}',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::redirectAction',
            ],
            'mautic_page_preview' => [
                'path'       => '/page/preview/{id}',
                'controller' => 'Mautic\PageBundle\Controller\PublicController::previewAction',
            ],
        ],
        'api' => [
            'mautic_api_pagesstandard' => [
                'standard_entity' => true,
                'name'            => 'pages',
                'path'            => '/pages',
                'controller'      => Mautic\PageBundle\Controller\Api\PageApiController::class,
            ],
        ],
        'catchall' => [
            'mautic_page_public' => [
                'path'         => '/{slug}',
                'controller'   => 'Mautic\PageBundle\Controller\PublicController::indexAction',
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
        'page' => [
            'class' => Mautic\PageBundle\Entity\Page::class,
        ],
    ],

    'services' => [
        'fixtures' => [
            'mautic.page.fixture.page_category' => [
                'class'     => Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.category.model.category'],
            ],
            'mautic.page.fixture.page' => [
                'class'     => Mautic\PageBundle\DataFixtures\ORM\LoadPageData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page'],
            ],
            'mautic.page.fixture.page_hit' => [
                'class'     => Mautic\PageBundle\DataFixtures\ORM\LoadPageHitData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['mautic.page.model.page'],
            ],
        ],
        'other' => [
            'mautic.page.helper.token' => [
                'class'     => Mautic\PageBundle\Helper\TokenHelper::class,
                'arguments' => 'mautic.page.model.page',
            ],
            'mautic.page.helper.tracking' => [
                'class'     => Mautic\PageBundle\Helper\TrackingHelper::class,
                'arguments' => [
                    'mautic.tracker.contact',
                    'mautic.cache.provider',
                    'mautic.helper.core_parameters',
                    'request_stack',
                ],
            ],
        ],
    ],

    'parameters' => [
        'cat_in_page_url'                       => false,
        'google_analytics'                      => null,
        'track_contact_by_ip'                   => false,
        'track_by_fingerprint'                  => false,
        'track_by_tracking_url'                 => false,
        'google_analytics_id'                   => null,
        'google_analytics_trackingpage_enabled' => false,
        'google_analytics_landingpage_enabled'  => false,
        'google_analytics_anonymize_ip'         => false,
        'facebook_pixel_id'                     => null,
        'facebook_pixel_trackingpage_enabled'   => false,
        'facebook_pixel_landingpage_enabled'    => false,
        'do_not_track_404_anonymous'            => false,
    ],
];
