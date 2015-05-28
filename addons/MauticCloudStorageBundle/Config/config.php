<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Cloud Storage',
    'description' => 'Enables integrations with Mautic supported cloud storage services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'services'    => array(
        'events' => array(
            'mautic.cloudstorage.remoteassetbrowse.subscriber' => array(
                'class' => 'MauticAddon\MauticCloudStorageBundle\EventListener\RemoteAssetBrowseSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.cloudstorage.openstack' => array(
                'class' => 'MauticAddon\MauticCloudStorageBundle\Form\Type\OpenStackType',
                'alias' => 'cloudstorage_openstack'
            ),
            'mautic.form.type.cloudstorage.rackspace' => array(
                'class' => 'MauticAddon\MauticCloudStorageBundle\Form\Type\RackspaceType',
                'alias' => 'cloudstorage_rackspace'
            )
        )
    ),
);
