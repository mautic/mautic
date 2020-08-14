<?php
/**
 * @author    Mautic
 * @copyright 2020 Mautic Contributors. All rights reserved
 *
 * @see http://mautic.org
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
return [
    'name'        => 'Mautic Trello',
    'description' => 'Create Trello cards from Mautic contacts',
    'version'     => '1.0.0',
    'routes'      => [
        'main' => [
            'plugin_create_cards_show_new' => [
                'path'       => '/trello/card/show-new/{contactId}',
                'controller' => 'MauticTrelloBundle:Card:showNewCard',
            ],
            'plugin_trello_card_add' => [
                'path'        => '/trello/card',
                'method'      => 'POST',
                'controller'  => 'MauticTrelloBundle:Card:add',
                'returnRoute' => '',
            ],
        ],
    ],
    'parameters' => [
        'favorite_board' => '',
    ],
    'services' => [
        'forms' => [
            'mautic.trello.form.card' => [
                'class'     => 'MauticPlugin\MauticTrelloBundle\Form\NewCardType',
                'arguments' => [
                    'mautic.trello.service.trello_api',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.trello.form.config' => [
                'class'     => 'MauticPlugin\MauticTrelloBundle\Form\ConfigType',
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.trello.service.trello_api',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'events' => [
            'mautic.channel.button.subscriber.trello' => [
                'class'     => \MauticPlugin\MauticTrelloBundle\Event\ButtonSubscriber::class,
                'arguments' => [
                    'router',
                    'translator',
                    'request_stack',
                    'mautic.helper.integration',
                ],
            ],
            'mautic.trello.event.config' => [
                'class'     => \MauticPlugin\MauticTrelloBundle\Event\ConfigSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'others' => [
            'mautic.trello.service.trello_api' => [
                'class'     => \MauticPlugin\MauticTrelloBundle\Service\TrelloApiService::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.trello' => [
                'class'     => \MauticPlugin\MauticTrelloBundle\Integration\TrelloIntegration::class,
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
