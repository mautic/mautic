<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\SyncService;

use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudgeService\SyncJudgeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess\SyncProcessFactoryInterface;

/**
 * Class SyncService
 * @package MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService
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
     * @var IntegrationEntityRepository
     */
    private $integrationEntityRepository;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * SyncService constructor.
     * @param IntegrationEntityRepository $integrationEntityRepository
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncJudgeInterface $syncJudgeService
     */
    public function __construct(
        IntegrationEntityRepository $integrationEntityRepository,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncJudgeInterface $syncJudgeService
    )
    {
        $this->integrationEntityRepository = $integrationEntityRepository;
        $this->internalSyncDataExchange = $internalSyncDataExchange;
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncJudgeService = $syncJudgeService;
    }

    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param int $fromTimestamp
     */
    public function processIntegrationSync(SyncDataExchangeInterface $syncDataExchangeService, $fromTimestamp)
    {
        $integrationMappingManual = $this->integrationEntityRepository->getIntegrationMappingManual($syncDataExchangeService->getIntegration());
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $fromTimestamp,
            $this->syncJudgeService,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $syncDataExchangeService
        );
        $integrationSyncProcess->execute();
    }
}
