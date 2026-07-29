<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Integration;

use Gaufrette\Adapter;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;

abstract class CloudStorageIntegration extends BasicIntegration implements BasicInterface
{
    protected ?Adapter $adapter = null;

    public function __construct(
        private readonly EncryptionService $encryptionService,
    ) {
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
     * @return array<string, string>
     */
    public function getSupportedFeatures(): array
    {
        return [ConfigFormFeaturesInterface::FEATURE_CLOUD_STORAGE => 'mautic.integration.form.feature.cloud_storage'];
    }

    /**
     * @return array<string, string>
     */
    protected function getDecryptedApiKeys(): array
    {
        return $this->encryptionService->decrypt($this->getIntegrationSettings()?->getApiKeys() ?? []);
    }
}
