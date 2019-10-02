<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncService;

use GuzzleHttp\Exception\ClientException;
use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\RelationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Notifier;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncProcess;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var RelationsHelper
     */
    private $relationsHelper;

    /**
     * @var IntegrationSyncProcess
     */
    private $integratinSyncProcess;

    /**
     * @var MauticSyncProcess
     */
    private $mauticSyncProcess;

    /**
     * @var SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Notifier
     */
    private $notifier;

    /**
     * @param MauticSyncDataExchange   $internalSyncDataExchange
     * @param SyncDateHelper           $syncDateHelper
     * @param MappingHelper            $mappingHelper
     * @param RelationsHelper          $relationsHelper
     * @param SyncIntegrationsHelper   $syncIntegrationsHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param Notifier                 $notifier
     * @param IntegrationSyncProcess   $integrationSyncProcess
     * @param MauticSyncProcess        $mauticSyncProcess
     */
    public function __construct(
        MauticSyncDataExchange $internalSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        MappingHelper $mappingHelper,
        RelationsHelper $relationsHelper,
        SyncIntegrationsHelper $syncIntegrationsHelper,
        EventDispatcherInterface $eventDispatcher,
        Notifier $notifier,
        IntegrationSyncProcess $integrationSyncProcess,
        MauticSyncProcess $mauticSyncProcess
    ) {
        $this->internalSyncDataExchange = $internalSyncDataExchange;
        $this->syncDateHelper           = $syncDateHelper;
        $this->mappingHelper            = $mappingHelper;
        $this->relationsHelper          = $relationsHelper;
        $this->syncIntegrationsHelper   = $syncIntegrationsHelper;
        $this->eventDispatcher          = $eventDispatcher;
        $this->notifier                 = $notifier;
        $this->integratinSyncProcess    = $integrationSyncProcess;
        $this->mauticSyncProcess        = $mauticSyncProcess;
    }

    /**
     * @param InputOptionsDAO $inputOptionsDAO
     *
     * @throws \MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException
     */
    public function processIntegrationSync(InputOptionsDAO $inputOptionsDAO): void
    {
        $integrationSyncProcess = new SyncProcess(
            $this->syncDateHelper,
            $this->mappingHelper,
            $this->relationsHelper,
            $this->integratinSyncProcess,
            $this->mauticSyncProcess,
            $this->eventDispatcher,
            $this->notifier,
            $this->syncIntegrationsHelper->getMappingManual($inputOptionsDAO->getIntegration()),
            $this->internalSyncDataExchange,
            $this->syncIntegrationsHelper->getSyncDataExchange($inputOptionsDAO->getIntegration()),
            $inputOptionsDAO,
            $this
        );

        DebugLogger::log(
            $inputOptionsDAO->getIntegration(),
            sprintf(
                'Starting %s sync from %s date/time',
                $inputOptionsDAO->isFirstTimeSync() ? 'first time' : 'subsequent',
                $inputOptionsDAO->getStartDateTime() ? $inputOptionsDAO->getStartDateTime()->format('Y-m-d H:i:s') : 'yet to be determined'
            ),
            __CLASS__.':'.__FUNCTION__
        );

        try {
            $integrationSyncProcess->execute();
        } catch (ClientException $exception) {
            // The sync failed to communicate with the integration so log it
            DebugLogger::log($inputOptionsDAO->getIntegration(), $exception->getMessage(), null, [], LogLevel::ERROR);
        }
    }

    /**
     * @param DebugLogger $logger
     */
    public function initiateDebugLogger(DebugLogger $logger): void
    {
        // Yes it's a hack to prevent from having to pass the logger as a dependency into dozens of classes
        // So not doing anything with the logger, just need Symfony to initiate the service
    }
}
