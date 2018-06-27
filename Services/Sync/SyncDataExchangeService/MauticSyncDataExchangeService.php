<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService;

use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncOrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncReportDAO;

/**
 * Class MauticSyncDataExchangeService
 * @package Mautic\PluginBundle\Model\Sync
 */
class MauticSyncDataExchangeService implements SyncDataExchangeServiceInterface
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * MauticSyncDataExchangeService constructor.
     * @param LeadRepository $leadRepository
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return 'mautic';
    }

    /**
     * @param IntegrationMappingManualDAO $integrationMapping
     * @param int|null $fromTimestamp
     * @return SyncReportDAO
     */
    public function getSyncReport(IntegrationMappingManualDAO $integrationMapping, $fromTimestamp = null)
    {
        $syncReport = new SyncReportDAO('mautic');
        $entities = $integrationMapping->getInternalEntities();
        foreach($entities as $entity) {
            switch($entity) {
                case 'lead':
            }
        }
    }

    /**
     * @param SyncOrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(SyncOrderDAO $syncOrderDAO)
    {

    }
}