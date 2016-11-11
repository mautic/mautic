<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name' => 'Clearbit',
    'description' => 'Enables integration with Clearbit for contact and company lookup',
    'version' => '1.0',
    'author' => 'Werner Garcia',

    'routes' => [
        'public' => [
            'mautic_plugin_clearbit_index'=>[
                'path' => '/clearbit/callback',
                'controller' => 'MauticClearbitBundle:Public:callback',
            ],
            'mautic_plugin_clearbit_compindex'=>[
                'path' => '/clearbit/compcallback',
                'controller' => 'MauticClearbitBundle:Public:compcallback',
            ],
        ],
        'main' => [
            'mautic_plugin_clearbit_action' => [
                'path' => '/clearbit/{objectAction}/{objectId}',
                'controller' => 'MauticClearbitBundle:Clearbit:execute',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.clearbit.button.subscriber' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\EventListener\ButtonSubscriber',
                'arguments' => [
                    'service_container'
                ],
            ],
            'mautic.plugin.clearbit.lead.subscriber' => [
                'class' => 'MauticPlugin\MauticClearbitBundle\EventListener\LeadSubscriber',
                'arguments' => [
                  'service_container'
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
    ],
];
