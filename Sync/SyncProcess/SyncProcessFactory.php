<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace MauticPlugin\IntegrationsBundle\Sync\SyncProcess;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

/**
 * Class SyncProcessFactory
 */
final class SyncProcessFactory implements SyncProcessFactoryInterface
{
    /**
     * @param SyncJudgeInterface        $syncJudge
     * @param MappingManualDAO          $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param SyncDateHelper            $syncDateHelper
     * @param bool                      $isFirstTimeSync
     * @param \DateTimeInterface|null   $syncFromDateTime
     *
     * @return SyncProcess|mixed
     */
    public function create(
        SyncJudgeInterface $syncJudge,
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        $isFirstTimeSync,
        \DateTimeInterface $syncFromDateTime = null
    ) {
        return new SyncProcess(
            $syncJudge,
            $integrationMappingManual,
            $internalSyncDataExchange,
            $integrationSyncDataExchange,
            $syncDateHelper,
            $isFirstTimeSync,
            $syncFromDateTime = null
        );
    }
}
