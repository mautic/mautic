<?php

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

    public function appendToForm(&$builder, $data, $formArea): void
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
                    'data'     => $data['provider'] ?? [],
                ]
            );
        } catch (NoFormNeededException) {
        }
    }

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
     * @throws NoFormNeededException
     */
    abstract public function getForm(): string;

    /**
     * Retrieves the public URL for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    abstract public function getPublicUrl($key);

    public function getSupportedFeatures()
    {
        return ['cloud_storage'];
    }
}
