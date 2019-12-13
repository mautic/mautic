<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Cloud Storage',
    'description' => 'Enables integrations with Mautic supported cloud storage services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'services' => [
        'events' => [
            'mautic.cloudstorage.remoteassetbrowse.subscriber' => [
                'class' => \MauticPlugin\MauticCloudStorageBundle\EventListener\RemoteAssetBrowseSubscriber::class,
            ],
        ],
        'forms' => [
            'mautic.form.type.cloudstorage.openstack' => [
                'class' => \MauticPlugin\MauticCloudStorageBundle\Form\Type\OpenStackType::class,
            ],
            'mautic.form.type.cloudstorage.rackspace' => [
                'class' => \MauticPlugin\MauticCloudStorageBundle\Form\Type\RackspaceType::class,
            ],
        ],
        'integrations' => [
            'mautic.integration.amazons3' => [
                'class'     => \MauticPlugin\MauticCloudStorageBundle\Integration\AmazonS3Integration::class,
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
                ],
            ],
            'mautic.integration.openstack' => [
                'class'     => \MauticPlugin\MauticCloudStorageBundle\Integration\OpenStackIntegration::class,
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
                ],
            ],
            'mautic.integration.rackspace' => [
                'class'     => \MauticPlugin\MauticCloudStorageBundle\Integration\RackspaceIntegration::class,
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
                ],
            ],
        ],
    ],
];
