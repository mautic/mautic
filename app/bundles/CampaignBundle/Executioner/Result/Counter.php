<?php

namespace Mautic\CampaignBundle\Executioner\Result;

class Counter
{
    /**
     * @var int
     */
    private $eventCount = 0;

    /**
     * @var int
     */
    private $evaluated = 0;

    /**
     * @var int
     */
    private $executed = 0;

    /**
     * @var int
     */
    private $totalEvaluated = 0;

    /**
     * @var int
     */
    private $totalExecuted = 0;

    /**
     * @var int
     */
    private $totalScheduled = 0;

    /**
     * Counter constructor.
     *
     * @param int $eventCount
     * @param int $evaluated
     * @param int $executed
     * @param int $totalEvaluated
     * @param int $totalExecuted
     * @param int $totalScheduled
     */
    public function __construct($eventCount = 0, $evaluated = 0, $executed = 0, $totalEvaluated = 0, $totalExecuted = 0, $totalScheduled = 0)
    {
        $this->eventCount     = $eventCount;
        $this->evaluated      = $evaluated;
        $this->executed       = $executed;
        $this->totalEvaluated = $totalEvaluated;
        $this->totalExecuted  = $totalExecuted;
        $this->totalScheduled = $totalScheduled;
    }

    /**
     * @return int
     */
    public function getEventCount()
    {
        return $this->eventCount;
    }

    /**
     * @param int $step
     */
    public function advanceEventCount($step = 1)
    {
        $this->eventCount += $step;
    }

    /**
     * @return int
     */
    public function getEvaluated()
    {
        return $this->evaluated;
    }

    /**
     * @param int $step
     */
    public function advanceEvaluated($step = 1)
    {
        $this->evaluated += $step;
        $this->totalEvaluated += $step;
    }

    /**
     * @return int
     */
    public function getExecuted()
    {
        return $this->executed;
    }

    /**
     * @param int $step
     */
    public function advanceExecuted($step = 1)
    {
        $this->executed += $step;
        $this->totalExecuted += $step;
    }

    /**
     * Includes all child events (conditions, etc) evaluated.
     *
     * @return int
     */
    public function getTotalEvaluated()
    {
        return $this->totalEvaluated;
    }

    /**
     * @param int $step
     */
    public function advanceTotalEvaluated($step = 1)
    {
        $this->totalEvaluated += $step;
    }

    /**
     * Includes all child events (conditions, etc) executed.
     *
     * @return int
     */
    public function getTotalExecuted()
    {
        return $this->totalExecuted;
    }

    /**
     * @param int $step
     */
    public function advanceTotalExecuted($step = 1)
    {
        $this->totalExecuted += $step;
    }

    /**
     * @return int
     */
    public function getTotalScheduled()
    {
        return $this->totalScheduled;
    }

    /**
     * @param int $step
     */
    public function advanceTotalScheduled($step = 1)
    {
        $this->totalScheduled += $step;
    }
}
