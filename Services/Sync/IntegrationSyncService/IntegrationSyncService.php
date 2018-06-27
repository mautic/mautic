<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService;

use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess\IntegrationSyncProcessFactoryInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeServiceInterface;
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
     * @var SyncDataExchangeServiceInterface
     */
    private $internalSyncDataExchange;

    /**
     * IntegrationSyncService constructor.
     * @param IntegrationEntityRepository $integrationEntityRepository
     * @param SyncDataExchangeServiceInterface $internalSyncDataExchange
     * @param IntegrationSyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncJudgeServiceInterface $syncJudgeService
     */
    public function __construct(
        IntegrationEntityRepository $integrationEntityRepository,
        SyncDataExchangeServiceInterface $internalSyncDataExchange,
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
     * @param SyncDataExchangeServiceInterface $integrationSyncDataExchange
     * @param int $fromTimestamp
     */
    public function processIntegrationSync(SyncDataExchangeServiceInterface $integrationSyncDataExchange, $fromTimestamp)
    {
        $integrationMappingManual = $this->integrationEntityRepository->getIntegrationMappingManual($integrationSyncDataExchange->getIntegration());
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $fromTimestamp,
            $this->syncJudgeService,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $integrationSyncDataExchange
        );
        $integrationSyncProcess->execute();
    }
}
