<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCloudStorageBundle\Integration;

use Gaufrette\Adapter\LazyOpenCloud;
use Gaufrette\Adapter\OpenStackCloudFiles\ObjectStoreFactory;
use OpenCloud\OpenStack;

/**
 * Class OpenStackIntegration
 */
class OpenStackIntegration extends CloudStorageIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'OpenStack';
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'username';
    }

    /**
     * Get the array key for client secret
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
        return array(
            'username' => 'mautic.integration.keyfield.OpenStack.username',
            'password' => 'mautic.integration.keyfield.OpenStack.password',
            'tenantName' => 'mautic.integration.keyfield.OpenStack.tenantName',
            'containerName' => 'mautic.integration.keyfield.OpenCloud.containerName'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return LazyOpenCloud
     */
    public function getAdapter()
    {
        $settings = $this->settings->getFeatureSettings();
        $keys     = $this->getDecryptedApiKeys();

        $connection = new OpenStack(
            $settings['provider']['serviceUrl'],
            array(
                'username' => $keys['username'],
                'password' => $keys['password'],
                'tenantName' => $keys['tenantName']
            )
        );

        $factory = new ObjectStoreFactory($connection);

        return new LazyOpenCloud($factory, $keys['containerName']);
    }
}
