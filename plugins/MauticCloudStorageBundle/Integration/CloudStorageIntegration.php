<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\Integration;

use Gaufrette\Adapter;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\DependencyInjection\Container;

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
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilder| \Symfony\Component\Form\Form $builder
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $name = strtolower($this->getName());
            if ($this->container->has('mautic.form.type.cloudstorage.'.$name)) {
                $builder->add('provider', $name, [
                    'label'    => 'mautic.integration.form.provider.settings',
                    'required' => false,
                    'data'     => (isset($data['provider'])) ? $data['provider'] : [],
                ]);
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
