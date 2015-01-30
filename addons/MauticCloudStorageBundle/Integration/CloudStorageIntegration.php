<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCloudStorageBundle\Integration;

use Mautic\AddonBundle\Integration\AbstractIntegration;

/**
 * Class CloudStorageIntegration
 */
abstract class CloudStorageIntegration extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'api';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array('cloud_storage');
    }
}
