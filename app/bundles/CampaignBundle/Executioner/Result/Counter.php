<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Result;

class Counter implements \ArrayAccess
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
     * Counts constructor.
     */
    public function __construct($eventCount = 0, $evaluated = 0, $executed = 0, $totalEvaluated = 0, $totalExecuted = 0)
    {
        $this->eventCount     = $eventCount;
        $this->evaluated      = $evaluated;
        $this->executed       = $executed;
        $this->totalEvaluated = $totalEvaluated;
        $this->totalExecuted  = $totalExecuted;
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
     * BC support for pre 2.13.0 array based counts.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$$offset);
    }

    /**
     * BC support for pre 2.13.0 array based counts.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return (isset($this->$$offset)) ? $this->$$offset : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        // ignore
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        // ignore
    }
}
