<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Citrix',
    'description' => 'Enables integration with Mautic supported Citrix collaboration products.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_citrix_proxy' => [
                'path'       => '/citrix/proxy',
                'controller' => 'MauticCitrixBundle:Public:proxy',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'MauticCitrixBundle:Public:sessionChanged',
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
            'mautic.citrix.leadbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
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
            'mautic.citrix.emailbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                    'event_dispatcher',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.citrix.stats.subscriber' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'mautic.security',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.citrix.integration.request' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\EventListener\IntegrationRequestSubscriber::class,
            ],
        ],
        'forms' => [
            'mautic.form.type.fieldslist.citrixlist' => [
                'class' => \MauticPlugin\MauticCitrixBundle\Form\Type\CitrixListType::class,
            ],
            'mautic.form.type.citrix.submitaction' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Form\Type\CitrixActionType::class,
                'arguments' => [
                    'mautic.form.model.field',
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Form\Type\CitrixCampaignEventType::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => \MauticPlugin\MauticCitrixBundle\Form\Type\CitrixCampaignActionType::class,
                'arguments' => [
                    'translator',
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
