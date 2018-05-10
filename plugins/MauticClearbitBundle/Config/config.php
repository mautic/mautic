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
                'class'     => 'MauticPlugin\MauticClearbitBundle\EventListener\ButtonSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.plugin.clearbit.lead.subscriber' => [
                'class'     => 'MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.plugin.clearbit.lookup_helper',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.clearbit_lookup' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\Form\Type\LookupType',
                'alias' => 'clearbit_lookup',
            ],
            'mautic.form.type.clearbit_batch_lookup' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\Form\Type\BatchLookupType',
                'alias' => 'clearbit_batch_lookup',
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
                ],
            ],
        ],
    ],
];
