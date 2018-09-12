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
use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;

class SyncIntegrationsHelper
{
    /**
     * @var BasicIntegration[]
     */
    private $integrations;

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
     * @param BasicIntegration $integration
     */
    public function addIntegration(BasicIntegration $integration)
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
            if ($integrationObject->hasIntegrationEntity()) {
                $integrationEntity = $integrationObject->getIntegrationEntity();

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

            $integrationObject->setIntegrationEntity($integrationEntity);
            if ($integrationEntity->getIsPublished()) {
                $this->enabled[] = $name;
            }
        }

        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function hasEnabledIntegrations()
    {
        return (bool) count($this->getEnabledIntegrations());
    }
}