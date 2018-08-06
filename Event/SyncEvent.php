<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Event;

use DateTimeImmutable;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use Symfony\Component\EventDispatcher\Event;

class SyncEvent extends Event
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var DateTimeImmutable
     */
    private $startDate;

    /**
     * @var SyncDataExchangeInterface
     */
    private $dataExchange;

    /**
     * @var MappingManualDAO
     */
    private $mappingManual;

    /**
     * @param string            $integration
     * @param DateTimeImmutable $startDate
     */
    public function __construct($integration, DateTimeImmutable $startDate)
    {
        $this->integration = $integration;
        $this->startDate   = $startDate;
    }

    /**
     * @param $integration
     *
     * @return bool
     */
    public function shouldIntegrationSync($integration): bool
    {
        return strtolower($this->integration) === strtolower($integration);
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param SyncDataExchangeInterface $dataExchange
     * @param MappingManualDAO          $mappingManualDAO
     */
    public function setSyncServices(SyncDataExchangeInterface $dataExchange, MappingManualDAO $mappingManualDAO)
    {
        $this->dataExchange  = $dataExchange;
        $this->mappingManual = $mappingManualDAO;

        $this->stopPropagation();
    }

    /**
     * @return SyncDataExchangeInterface
     */
    public function getDataExchange(): SyncDataExchangeInterface
    {
        return $this->dataExchange;
    }

    /**
     * @return MappingManualDAO
     */
    public function getMappingManual(): MappingManualDAO
    {
        return $this->mappingManual;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }
}
