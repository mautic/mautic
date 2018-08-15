<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncDateHelper;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;

/**
 * Class SyncProcessFactory
 */
final class SyncProcessFactory implements SyncProcessFactoryInterface
{
    /**
     * @param SyncJudgeInterface        $syncJudgeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param SyncDateHelper            $syncDateHelper
     * @param \DateTimeInterface|null   $syncFromDateTime
     *
     * @return SyncProcess
     */
    public function create(
        SyncJudgeInterface $syncJudgeService,
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        \DateTimeInterface $syncFromDateTime = null
    ) {
        return new SyncProcess(
            $syncJudgeService,
            $integrationMappingManual,
            $internalSyncDataExchange,
            $integrationSyncDataExchange,
            $syncDateHelper,
            $syncFromDateTime = null
        );
    }
}
