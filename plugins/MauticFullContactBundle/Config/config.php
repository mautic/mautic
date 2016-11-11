<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name' => 'FullContact',
    'description' => 'Enables integration with FullContact for contact and company lookup',
    'version' => '1.0',
    'author' => 'Werner Garcia',

    'routes' => [
        'public' => [
            'mautic_plugin_fullcontact_index'=>[
                'path' => '/fullcontact/callback',
                'controller' => 'MauticFullContactBundle:Public:callback',
            ],
            'mautic_plugin_fullcontact_compindex'=>[
                'path' => '/fullcontact/compcallback',
                'controller' => 'MauticFullContactBundle:Public:compcallback',
            ],
        ],
        'main' => [
            'mautic_plugin_fullcontact_action' => [
                'path' => '/fullcontact/{objectAction}/{objectId}',
                'controller' => 'MauticFullContactBundle:FullContact:execute',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.fullcontact.button.subscriber' => [
                'class' => 'MauticPlugin\MauticFullContactBundle\EventListener\ButtonSubscriber',
                'arguments' => [
                    'service_container'
                ],
            ],
            'mautic.plugin.fullcontact.lead.subscriber' => [
                'class' => 'MauticPlugin\MauticFullContactBundle\EventListener\LeadSubscriber',
                'arguments' => [
                  'service_container'
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.fullcontact_lookup' => [
                'class' => 'MauticPlugin\MauticFullContactBundle\Form\Type\LookupType',
                'alias' => 'fullcontact_lookup',
            ],
            'mautic.form.type.fullcontact_batch_lookup' => [
                'class' => 'MauticPlugin\MauticFullContactBundle\Form\Type\BatchLookupType',
                'alias' => 'fullcontact_batch_lookup',
            ],
        ],
    ],
];
