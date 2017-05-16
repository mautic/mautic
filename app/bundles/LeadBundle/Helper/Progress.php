<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

class Progress
{
    /**
     * Total number of items representing 100%.
     *
     * @var int
     */
    protected $total;

    /**
     * Currently proccessed items.
     *
     * @var int
     */
    protected $done;

    /**
     * Returns count of all items.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Returns count of processed items.
     *
     * @return int
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Increase done count by 1.
     *
     * @return Progress
     */
    public function increase()
    {
        ++$this->done;

        return $this;
    }

    /**
     * Checked if the progress is 100 or more %.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->done >= $this->total;
    }

    /**
     * Bind Progress from simple array.
     *
     * @param array $progress
     *
     * @return Progress
     */
    public function bindArray(array $progress)
    {
        if (isset($progress[0])) {
            $this->done = (int) $progress[0];
        }

        if (isset($progress[1])) {
            $this->total = (int) $progress[1];
        }

        return $this;
    }

    /**
     * Convert this object to a simple array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            $this->done,
            $this->total,
        ];
    }

    /**
     * Counts percentage of the progress.
     *
     * @return int
     */
    public function toPercent()
    {
        return ($this->total) ? ceil(($this->done / $this->total) * 100) : 100;
    }
}
