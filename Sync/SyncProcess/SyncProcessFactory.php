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
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @param MappingHelper             $mappingHelper
     * @param \DateTimeInterface|null   $isFirstTimeSync
     * @param EventDispatcherInterface  $eventDispatcher
     * @param \DateTimeInterface|null   $syncFromDateTime
     * @param \DateTimeInterface|null   $syncToDateTime
     *
     * @return SyncProcess
     */
    public function create(
        SyncJudgeInterface $syncJudge,
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        MappingHelper $mappingHelper,
        $isFirstTimeSync,
        EventDispatcherInterface $eventDispatcher,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    ): SyncProcess {
        return new SyncProcess(
            $syncJudge,
            $integrationMappingManual,
            $internalSyncDataExchange,
            $integrationSyncDataExchange,
            $syncDateHelper,
            $mappingHelper,
            $isFirstTimeSync,
            $eventDispatcher,
            $syncFromDateTime,
            $syncToDateTime
        );
    }
}
