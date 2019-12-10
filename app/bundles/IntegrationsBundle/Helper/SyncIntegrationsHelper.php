<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helper;

use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class SyncIntegrationsHelper
{
    /**
     * @var SyncInterface[]
     */
    private $integrations = [];

    /**
     * @var null|array
     */
    private $enabled;

    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    /**
     * @param IntegrationsHelper $integrationsHelper
     * @param ObjectProvider     $objectProvider
     */
    public function __construct(IntegrationsHelper $integrationsHelper, ObjectProvider $objectProvider)
    {
        $this->integrationsHelper = $integrationsHelper;
        $this->objectProvider     = $objectProvider;
    }

    /**
     * @param SyncInterface $integration
     */
    public function addIntegration(SyncInterface $integration): void
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @param string $integration
     *
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
     * @return array|null
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
            } catch (IntegrationNotFoundException $exception) {
                // Just ignore as the plugin hasn't been installed yet
            }
        }

        return $this->enabled;
    }

    /**
     * @param string $mauticObject
     *
     * @return bool
     *
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
                        return true;
                    }
                }
            } catch (ObjectNotFoundException $exception) {
                // Object is not supported so just continue
            }
        }

        return false;
    }

    /**
     * @param string $integration
     *
     * @return MappingManualDAO
     *
     * @throws IntegrationNotFoundException
     */
    public function getMappingManual(string $integration): MappingManualDAO
    {
        $integration = $this->getIntegration($integration);

        return $integration->getMappingManual();
    }

    /**
     * @param string $integration
     *
     * @return SyncDataExchangeInterface
     *
     * @throws IntegrationNotFoundException
     */
    public function getSyncDataExchange(string $integration): SyncDataExchangeInterface
    {
        $integration = $this->getIntegration($integration);

        return $integration->getSyncDataExchange();
    }
}
