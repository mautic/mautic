<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Result;

final class Counter
{
    private int $rescheduled = 0;

    public function __construct(
        private int $eventCount = 0,
        private int $evaluated = 0,
        private int $executed = 0,
        private int $totalEvaluated = 0,
        private int $totalExecuted = 0,
        private int $totalScheduled = 0,
    ) {
    }

    public function getEventCount(): int
    {
        return $this->eventCount;
    }

    /**
     * @param int $step
     */
    public function advanceEventCount($step = 1): void
    {
        $this->eventCount += $step;
    }

    public function advanceRescheduled(int $step = 1): void
    {
        $this->rescheduled += $step;
    }

    public function getRescheduled(): int
    {
        return $this->rescheduled;
    }

    public function getEvaluated(): int
    {
        return $this->evaluated;
    }

    /**
     * @param int $step
     */
    public function advanceEvaluated($step = 1): void
    {
        $this->evaluated += $step;
        $this->totalEvaluated += $step;
    }

    public function getExecuted(): int
    {
        return $this->executed;
    }

    /**
     * @param int $step
     */
    public function advanceExecuted($step = 1): void
    {
        $this->executed += $step;
        $this->totalExecuted += $step;
    }

    /**
     * Includes all child events (conditions, etc) evaluated.
     */
    public function getTotalEvaluated(): int
    {
        return $this->totalEvaluated;
    }

    /**
     * @param int $step
     */
    public function advanceTotalEvaluated($step = 1): void
    {
        $this->totalEvaluated += $step;
    }

    /**
     * Includes all child events (conditions, etc) executed.
     */
    public function getTotalExecuted(): int
    {
        return $this->totalExecuted;
    }

    /**
     * @param int $step
     */
    public function advanceTotalExecuted($step = 1): void
    {
        $this->totalExecuted += $step;
    }

    public function getTotalScheduled(): int
    {
        return $this->totalScheduled;
    }

    /**
     * @param int $step
     */
    public function advanceTotalScheduled($step = 1): void
    {
        $this->totalScheduled += $step;
    }
}
