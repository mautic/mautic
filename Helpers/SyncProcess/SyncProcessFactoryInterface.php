<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace MauticPlugin\IntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncDateHelper;

/**
 * Interface SyncProcessFactoryInterface
 */
interface SyncProcessFactoryInterface
{
    /**
     * @param MappingManualDAO          $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param SyncDateHelper            $syncDateHelper
     * @param \DateTimeInterface|null   $syncFromDateTime
     *
     * @return SyncProcess
     */
    public function create(
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        \DateTimeInterface $syncFromDateTime = null
    );
}