<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderResultsDAO;
use Symfony\Contracts\EventDispatcher\Event;

class CompletedSyncIterationEvent extends Event
{
    /**
     * @var OrderResultsDAO
     */
    private $orderResultsDAO;

    /**
     * @var int
     */
    private $iteration;

    /**
     * @var InputOptionsDAO
     */
    private $inputOptionsDAO;

    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    public function __construct(
        OrderResultsDAO $orderResultsDAO,
        int $iteration,
        InputOptionsDAO $inputOptionsDAO,
        MappingManualDAO $mappingManualDAO
    ) {
        $this->orderResultsDAO  = $orderResultsDAO;
        $this->iteration        = $iteration;
        $this->inputOptionsDAO  = $inputOptionsDAO;
        $this->mappingManualDAO = $mappingManualDAO;
    }

    public function getIntegration(): string
    {
        return $this->mappingManualDAO->getIntegration();
    }

    public function getOrderResults(): OrderResultsDAO
    {
        return $this->orderResultsDAO;
    }

    public function getIteration(): int
    {
        return $this->iteration;
    }

    public function getInputOptions(): InputOptionsDAO
    {
        return $this->inputOptionsDAO;
    }

    public function getMappingManual(): MappingManualDAO
    {
        return $this->mappingManualDAO;
    }
}
