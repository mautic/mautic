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

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;

/**
 * Class AmazonS3Integration.
 */
class AmazonS3Integration extends CloudStorageIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AmazonS3';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'Amazon S3';
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret',
            'bucket'        => 'mautic.integration.keyfield.amazons3.bucket',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return AwsS3
     */
    public function getAdapter()
    {
        if (!$this->adapter) {
            $keys = $this->getDecryptedApiKeys();

            $service = S3Client::factory(['key' => $keys['client_id'], 'secret' => $keys['client_secret']]);

            $this->adapter = new AwsS3($service, $keys['bucket']);
        }

        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicUrl($key)
    {
        return $this->getAdapter()->getUrl($key);
    }
}
