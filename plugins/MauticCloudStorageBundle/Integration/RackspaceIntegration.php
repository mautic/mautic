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

use Gaufrette\Adapter\LazyOpenCloud;
use Gaufrette\Adapter\OpenStackCloudFiles\ObjectStoreFactory;
use OpenCloud\Rackspace;

/**
 * Class RackspaceIntegration.
 */
class RackspaceIntegration extends CloudStorageIntegration
{
    /**
     * @var Rackspace
     */
    private $connection;

    /**
     * @var ObjectStoreFactory
     */
    private $storeFactory;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Rackspace';
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'username';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'apiKey';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'username'      => 'mautic.integration.keyfield.Rackspace.username',
            'apiKey'        => 'mautic.integration.keyfield.Rackspace.apiKey',
            'containerName' => 'mautic.integration.keyfield.OpenCloud.containerName',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return LazyOpenCloud
     */
    public function getAdapter()
    {
        if (!$this->adapter) {
            $settings = $this->settings->getFeatureSettings();
            $keys     = $this->getDecryptedApiKeys();

            switch ($settings['provider']['serverLocation']) {
                case 'us':
                default:
                    $url = Rackspace::US_IDENTITY_ENDPOINT;
                    break;
                case 'uk':
                    $url = Rackspace::UK_IDENTITY_ENDPOINT;
                    break;
            }

            $this->connection = new Rackspace(
                $url,
                [
                    'username' => $keys['username'],
                    'apiKey'   => $keys['apiKey'],
                ]
            );

            $this->storeFactory = new ObjectStoreFactory($this->connection);

            $this->adapter = new LazyOpenCloud($this->storeFactory, $keys['containerName']);
        }

        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicUrl($key)
    {
        $keys = $this->getDecryptedApiKeys();

        return $this->storeFactory->getObjectStore()->getContainer($keys['containerName'])->getObject($key)->getPublicUrl();
    }
}
