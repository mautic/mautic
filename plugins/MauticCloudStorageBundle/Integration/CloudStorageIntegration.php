<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\Integration;

use Gaufrette\Adapter;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class CloudStorageIntegration.
 */
abstract class CloudStorageIntegration extends AbstractIntegration
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'api';
    }

    /**
     * Retrieves an Adapter object for this integration.
     *
     * @return Adapter
     */
    abstract public function getAdapter();

    /**
     * Retrieves the public URL for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    abstract public function getPublicUrl($key);

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return ['cloud_storage'];
    }
}
