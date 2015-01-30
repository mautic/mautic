<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCloudStorageBundle\Integration;

use Gaufrette\Adapter;
use Mautic\AddonBundle\Integration\AbstractIntegration;

/**
 * Class CloudStorageIntegration
 */
abstract class CloudStorageIntegration extends AbstractIntegration
{
    /**
     * @param FormBuilder|Form $builder
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $name = strtolower($this->getName());
            if ($this->factory->serviceExists('mautic.form.type.cloudstorage.' . $name)) {
                $builder->add('provider', 'cloudstorage_' . $name, array(
                    'label'    => 'mautic.integration.form.provider.settings',
                    'required' => false,
                    'data'     => (isset($data['provider'])) ? $data['provider'] : array()
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'api';
    }

    /**
     * Retrieves a connector object for this integration
     *
     * @return Adapter
     */
    abstract public function getConnector();

    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return array('cloud_storage');
    }
}
