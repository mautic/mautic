<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderResultsDAO;
use Symfony\Component\EventDispatcher\Event;

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

    public function getOrderStatus(): OrderResultsDAO
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
