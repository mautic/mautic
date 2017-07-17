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
use OpenCloud\OpenStack;

/**
 * Class OpenStackIntegration.
 */
class OpenStackIntegration extends CloudStorageIntegration
{
    /**
     * @var OpenStack
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
        return 'OpenStack';
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
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'username'      => 'mautic.integration.keyfield.OpenStack.username',
            'password'      => 'mautic.integration.keyfield.OpenStack.password',
            'tenantName'    => 'mautic.integration.keyfield.OpenStack.tenantName',
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

            $this->connection = new OpenStack(
                $settings['provider']['serviceUrl'],
                [
                    'username'   => $keys['username'],
                    'password'   => $keys['password'],
                    'tenantName' => $keys['tenantName'],
                ]
            );

            $this->storeFactory = new ObjectStoreFactory($this->connection, null, 'publicURL');

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
