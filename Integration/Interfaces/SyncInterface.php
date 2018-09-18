<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

interface SyncInterface
{
    /**
     * Return the integration's name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @return MappingManualDAO
     */
    public function getMappingManual(): MappingManualDAO;

    /**
     * @return SyncDataExchangeInterface
     */
    public function getSyncDataExchange(): SyncDataExchangeInterface;

    /**
     * @return bool
     */
    public function hasIntegration(): bool;

    /**
     * @return Integration
     */
    public function getIntegration(): Integration;

    /**
     * @param Integration $integration
     *
     * @return mixed
     */
    public function setIntegration(Integration $integration);
}