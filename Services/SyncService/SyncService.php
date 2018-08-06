<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Services\SyncService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess\SyncProcessFactoryInterface;

/**
 * Class SyncService
 */
final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncProcessFactoryInterface
     */
    private $integrationSyncProcessFactory;

    /**
     * @var SyncJudgeInterface
     */
    private $syncJudgeService;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * SyncService constructor.
     *
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncJudgeInterface          $syncJudgeService
     * @param MauticSyncDataExchange      $internalSyncDataExchange
     */
    public function __construct(
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncJudgeInterface $syncJudgeService,
        MauticSyncDataExchange $internalSyncDataExchange
    ) {
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncJudgeService              = $syncJudgeService;
        $this->internalSyncDataExchange      = $internalSyncDataExchange;
    }

    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param \DateTimeImmutable        $fromDateTime
     *
     * @return mixed|void
     */
    public function processIntegrationSync(
        SyncDataExchangeInterface $syncDataExchangeService,
        MappingManualDAO $integrationMappingManual,
        \DateTimeImmutable $fromDateTime
    ) {
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $fromDateTime->getTimestamp(),
            $this->syncJudgeService,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $syncDataExchangeService
        );
        $integrationSyncProcess->execute();
    }
}
