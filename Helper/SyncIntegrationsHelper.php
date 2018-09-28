<?php

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
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
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
     * SyncIntegrationsHelper constructor.
     *
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * @param SyncInterface $integration
     */
    public function addIntegration(SyncInterface $integration)
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
    public function hasObjectSyncEnabled(string $mauticObject)
    {
        if (MauticSyncDataExchange::OBJECT_CONTACT !== $mauticObject && MauticSyncDataExchange::OBJECT_COMPANY !== $mauticObject) {
            throw new ObjectNotFoundException($mauticObject);
        }

        $enabledIntegrations = $this->getEnabledIntegrations();

        foreach ($enabledIntegrations as $integration) {
            $syncIntegration     = $this->getIntegration($integration);
            $featureSettings     = $syncIntegration->getIntegrationConfiguration()->getFeatureSettings();

            if (!isset($featureSettings['objects'])) {
                continue;
            }

            // Find what object is mapped to Mautic's object
            $mappingManual     = $syncIntegration->getMappingManual();
            $mappedObjectNames = $mappingManual->getMappedIntegrationObjectsNames($mauticObject);
            foreach ($mappedObjectNames as $mappedObjectName) {
                if (in_array($mappedObjectName, $featureSettings['objects'])) {
                    return true;
                }
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
