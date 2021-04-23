<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Clearbit',
    'description' => 'Enables integration with Clearbit for contact and company lookup',
    'version'     => '1.0',
    'author'      => 'Werner Garcia',

    'routes' => [
        'public' => [
            'mautic_plugin_clearbit_index' => [
                'path'       => '/clearbit/callback',
                'controller' => 'MauticClearbitBundle:Public:callback',
            ],
        ],
        'main' => [
            'mautic_plugin_clearbit_action' => [
                'path'       => '/clearbit/{objectAction}/{objectId}',
                'controller' => 'MauticClearbitBundle:Clearbit:execute',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.clearbit.button.subscriber' => [
                'class'     => \MauticPlugin\MauticClearbitBundle\EventListener\ButtonSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                    'router',
                ],
            ],
            'mautic.plugin.clearbit.lead.subscriber' => [
                'class'     => \MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.plugin.clearbit.lookup_helper',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.clearbit_lookup' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\Form\Type\LookupType',
            ],
            'mautic.form.type.clearbit_batch_lookup' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\Form\Type\BatchLookupType',
            ],
        ],
        'others' => [
            'mautic.plugin.clearbit.lookup_helper' => [
                'class'     => 'MauticPlugin\MauticClearbitBundle\Helper\LookupHelper',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.clearbit' => [
                'class'     => \MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration::class,
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
