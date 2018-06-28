<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService;

use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess\IntegrationSyncProcessFactoryInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService\SyncJudgeServiceInterface;

/**
 * Class IntegrationSyncService
 * @package Mautic\PluginBundle\Model\Sync
 */
final class IntegrationSyncService implements IntegrationSyncServiceInterface
{
    /** @var IntegrationSyncProcessFactoryInterface */
    private $integrationSyncProcessFactory;

    /** @var SyncJudgeServiceInterface */
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
     * IntegrationSyncService constructor.
     * @param IntegrationEntityRepository $integrationEntityRepository
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param IntegrationSyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncJudgeServiceInterface $syncJudgeService
     */
    public function __construct(
        IntegrationEntityRepository $integrationEntityRepository,
        SyncDataExchangeInterface $internalSyncDataExchange,
        IntegrationSyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncJudgeServiceInterface $syncJudgeService
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
