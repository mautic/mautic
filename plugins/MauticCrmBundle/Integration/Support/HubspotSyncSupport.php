<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\MappingManualFactory;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\SyncDataExchange;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag(name: 'mautic.sync_integration')]
final class HubspotSyncSupport extends HubspotIntegration implements SyncInterface
{
    /**
     * @param \Closure(): SyncServiceInterface $syncService
     */
    public function __construct(
        #[AutowireServiceClosure(SyncServiceInterface::class)]
        \Closure $syncService,
        Config $config,
        TranslatorInterface $translator,
        private readonly MappingManualFactory $mappingManualFactory,
        private readonly SyncDataExchange $syncDataExchange,
    ) {
        parent::__construct($syncService, $config, $translator);
    }

    public function getMappingManual(): MappingManualDAO
    {
        return $this->mappingManualFactory->getManual();
    }

    public function getSyncDataExchange(): SyncDataExchangeInterface
    {
        return $this->syncDataExchange;
    }
}
