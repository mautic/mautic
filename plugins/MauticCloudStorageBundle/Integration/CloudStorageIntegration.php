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
use MauticPlugin\MauticCloudStorageBundle\Exception\NoFormNeededException;

abstract class CloudStorageIntegration extends AbstractIntegration
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' !== $formArea) {
            return;
        }

        try {
            $builder->add(
                'provider',
                $this->getForm(),
                [
                    'label'    => 'mautic.integration.form.provider.settings',
                    'required' => false,
                    'data'     => (isset($data['provider'])) ? $data['provider'] : [],
                ]
            );
        } catch (NoFormNeededException $e) {
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
     * Retrieves an Adapter object for this integration.
     *
     * @return Adapter
     */
    abstract public function getAdapter();

    /**
     * Retrieves FQCN form type class name.
     *
     * @return string
     *
     * @throws NoFormNeededException
     */
    abstract public function getForm();

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
