<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCloudStorageBundle\Integration;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;

/**
 * Class AmazonS3Integration
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
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return array(
            'client_id'      => 'mautic.integration.keyfield.clientid',
            'client_secret'  => 'mautic.integration.keyfield.clientsecret'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return AwsS3
     */
    public function getAdapter()
    {
        $settings = $this->settings->getFeatureSettings();
        $keys     = $this->getDecryptedApiKeys();

        $service = S3Client::factory(array('key' => $keys['client_id'], 'secret' => $keys['client_secret']));

        return new AwsS3($service, $settings['provider']['bucket']);
    }
}
