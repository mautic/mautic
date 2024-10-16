<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class SyncIntegrationsHelper
{
    /**
     * @var SyncInterface[]
     */
    private array $integrations = [];

    /**
     * @var array<int,string>|null
     */
    private ?array $enabled = null;

    public function __construct(
        private IntegrationsHelper $integrationsHelper,
        private ObjectProvider $objectProvider
    ) {
    }

    public function addIntegration(SyncInterface $integration): void
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @return SyncInterface
     *
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration)
    {
        if (!isset($this->integrations[$integration])) {
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.sync_integration");
        }

        return $this->integrations[$integration];
    }

    /**
     * @return array<int,string>|null
     *
     * @throws IntegrationNotFoundException
     */
    public function getEnabledIntegrations()
    {
        if (null !== $this->enabled) {
            return $this->enabled;
        }

        $this->enabled = [];
        foreach ($this->integrations as $name => $syncIntegration) {
            try {
                $integrationConfiguration = $this->integrationsHelper->getIntegrationConfiguration($syncIntegration);

                if ($integrationConfiguration->getIsPublished()) {
                    $this->enabled[] = $name;
                }
            } catch (IntegrationNotFoundException) {
                // Just ignore as the plugin hasn't been installed yet
            }
        }

        return $this->enabled;
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function hasObjectSyncEnabled(string $mauticObject): bool
    {
        // Ensure the internal object exists.
        $this->objectProvider->getObjectByName($mauticObject);

        $enabledIntegrations = $this->getEnabledIntegrations();

        foreach ($enabledIntegrations as $integration) {
            $syncIntegration          = $this->getIntegration($integration);
            $integrationConfiguration = $syncIntegration->getIntegrationConfiguration();

            // Sync is enabled
            $enabledFeatures = $integrationConfiguration->getSupportedFeatures();
            if (!in_array(ConfigFormFeaturesInterface::FEATURE_SYNC, $enabledFeatures)) {
                continue;
            }

            // At least one object is enabled
            $featureSettings = $integrationConfiguration->getFeatureSettings();
            if (empty($featureSettings['sync']['objects'])) {
                continue;
            }

            try {
                // Find what object is mapped to Mautic's object
                $mappingManual     = $syncIntegration->getMappingManual();
                $mappedObjectNames = $mappingManual->getMappedIntegrationObjectsNames($mauticObject);
                foreach ($mappedObjectNames as $mappedObjectName) {
                    if (in_array($mappedObjectName, $featureSettings['sync']['objects'])) {
                        if (isset($featureSettings['sync']['directions'])
                            && isset($featureSettings['sync']['directions'][$mappedObjectName])) { // fallback condition
                            // check the syncDirection (consolidated flag) of the object
                            return ObjectMappingDAO::SYNC_TO_MAUTIC != $featureSettings['sync']['directions'][$mappedObjectName];
                        } else {
                            return true;
                        }
                    }
                }
            } catch (ObjectNotFoundException) {
                // Object is not supported so just continue
            }
        }

        return false;
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getMappingManual(string $integration): MappingManualDAO
    {
        $integration = $this->getIntegration($integration);

        return $integration->getMappingManual();
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getSyncDataExchange(string $integration): SyncDataExchangeInterface
    {
        $integration = $this->getIntegration($integration);

        return $integration->getSyncDataExchange();
    }
}
