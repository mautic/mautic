<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Helper;


use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
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
     * @var IntegrationRepository
     */
    private $integrationRepository;

    /**
     * @var null|array
     */
    private $enabled;

    /**
     * SyncIntegrationHelper constructor.
     *
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(IntegrationRepository $integrationRepository)
    {
        $this->integrationRepository = $integrationRepository;
    }

    /**
     * @param SyncInterface $integration
     */
    public function addIntegration(SyncInterface $integration)
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @return array|null
     */
    public function getEnabledIntegrations()
    {
        if (null !== $this->enabled) {
            return $this->enabled;
        }

        $this->enabled = [];
        foreach ($this->integrations as $name => $integrationObject) {
            if ($integrationObject->hasIntegration()) {
                $integrationEntity = $integrationObject->getIntegration();

                if ($integrationEntity->getIsPublished()) {
                    $this->enabled[] = $name;
                    continue;
                }
            }

            /** @var Integration $integrationEntity */
            $integrationEntity = $this->integrationRepository->findOneBy(['name' => $name]);
            if (!$integrationEntity) {
                continue;
            }

            $integrationObject->setIntegration($integrationEntity);
            if ($integrationEntity->getIsPublished()) {
                $this->enabled[] = $name;
            }
        }

        return $this->enabled;
    }

    /**
     * @param string $integration
     * @param string $mauticObject
     *
     * @return bool
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
            $integration     = $this->getIntegration($integration);
            $featureSettings = $integration->getIntegration()->getFeatureSettings();

            if (!isset($featureSettings['objects'])) {
                continue;
            }

            // Find what object is mapped to Mautic's object
            $mappingManual     = $integration->getMappingManual();
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
     * @throws IntegrationNotFoundException
     */
    public function getSyncDataExchange(string $integration): SyncDataExchangeInterface
    {
        $integration = $this->getIntegration($integration);

        return $integration->getSyncDataExchange();
    }

    /**
     * @param string $integration
     *
     * @return SyncInterface
     * @throws IntegrationNotFoundException
     */
    private function getIntegration(string $integration)
    {
        $this->getEnabledIntegrations();

        if (!isset($this->integrations[$integration])){
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.sync_integration");
        }

        return $this->integrations[$integration];
    }
}