<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MaintenanceEvent.
 */
class MaintenanceEvent extends Event
{
    /**
     * @var int
     */
    protected $daysOld;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var array
     */
    protected $stats = [];

    /**
     * @var bool
     */
    protected $dryRun = false;

    /**
     * @var bool
     */
    protected $gdpr = false;

    /**
     * @var array
     */
    protected $debug = [];

    /**
     * MaintenanceEvent constructor.
     *
     * @param int  $daysOld
     * @param bool $dryRun
     */
    public function __construct($daysOld, $dryRun, $gdpr)
    {
        $this->daysOld = (int) $daysOld;
        $this->dryRun  = (bool) $dryRun;
        $this->date    = new \DateTime("$daysOld days ago", new \DateTimeZone('UTC'));
        $this->gdpr    = (bool) $gdpr;
    }

    /**
     * Get integer for number of days ago to purge data.
     *
     * @return int
     */
    public function getDays()
    {
        return $this->daysOld;
    }

    /**
     * Returns a DateTime in UTC for the date to delete records older than the given date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the number of records purged by the listener.
     *
     * @param string $key
     * @param int    $recordCount
     */
    public function setStat($key, $recordCount, $sql = null, $parameters = [])
    {
        $this->stats[$key] = (int) $recordCount;

        if ($sql) {
            foreach ($parameters as $paramKey => $value) {
                $sql = str_replace(":$paramKey", $value, $sql);
            }
            $this->debug[$key] = $sql;
        }
    }

    /**
     * @return array
     */
    public function getStats()
    {
        ksort($this->stats, SORT_NATURAL);

        return $this->stats;
    }

    /**
     * Return if this is to be a dry run.
     *
     * @return bool
     */
    public function isDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @return array
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @return bool
     */
    public function isGdpr()
    {
        return $this->gdpr;
    }
}
