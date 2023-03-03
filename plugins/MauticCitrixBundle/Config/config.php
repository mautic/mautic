<?php

return [
    'name'        => 'Citrix',
    'description' => 'Enables integration with Mautic supported Citrix collaboration products.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_citrix_proxy' => [
                'path'       => '/citrix/proxy',
                'controller' => 'MauticPlugin\MauticCitrixBundle\Controller\PublicController::proxyAction',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'MauticPlugin\MauticCitrixBundle\Controller\PublicController::sessionChangedAction',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.citrix.formbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.templating',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                    'mautic.helper.templating',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
        ],
        'models' => [
            'mautic.citrix.model.citrix' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Model\CitrixModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.event',
                ],
            ],
        ],
        'fixtures' => [
            'mautic.citrix.fixture.load_citrix_data' => [
                'class'     => MauticPlugin\MauticCitrixBundle\Tests\DataFixtures\ORM\LoadCitrixData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
                'arguments' => ['doctrine.orm.entity_manager'],
                'optional'  => true,
            ],
        ],
        'integrations' => [
            'mautic.integration.gotoassist' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Integration\GotoassistIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gotomeeting' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Integration\GotomeetingIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gototraining' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Integration\GototrainingIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gotowebinar' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Integration\GotowebinarIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
