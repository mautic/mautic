<?php

/*
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'FullContact',
    'description' => 'Enables integration with FullContact for contact and company lookup',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'public' => [
            'mautic_plugin_fullcontact_index' => [
                'path'       => '/fullcontact/callback',
                'controller' => 'MauticFullContactBundle:Public:callback',
            ],
        ],
        'main' => [
            'mautic_plugin_fullcontact_action' => [
                'path'       => '/fullcontact/{objectAction}/{objectId}',
                'controller' => 'MauticFullContactBundle:FullContact:execute',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.fullcontact.button.subscriber' => [
                'class'     => \MauticPlugin\MauticFullContactBundle\EventListener\ButtonSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                    'router',
                ],
            ],
            'mautic.plugin.fullcontact.lead.subscriber' => [
                'class'     => \MauticPlugin\MauticFullContactBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.plugin.fullcontact.lookup_helper',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.fullcontact_lookup' => [
                'class' => \MauticPlugin\MauticFullContactBundle\Form\Type\LookupType::class,
            ],
            'mautic.form.type.fullcontact_batch_lookup' => [
                'class' => \MauticPlugin\MauticFullContactBundle\Form\Type\BatchLookupType::class,
            ],
        ],
        'others' => [
            'mautic.plugin.fullcontact.lookup_helper' => [
                'class'     => 'MauticPlugin\MauticFullContactBundle\Helper\LookupHelper',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'router',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.fullcontact' => [
                'class'     => \MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration::class,
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
