<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MaintenanceEvent extends Event
{
    protected int $daysOld;

    protected \DateTimeInterface $date;

    /**
     * @var array
     */
    protected $stats = [];

    /**
     * @var array
     */
    protected $debug = [];

    /**
     * @param int $daysOld
     */
    public function __construct(
        $daysOld,
        protected bool $dryRun,
        protected bool $gdpr,
    ) {
        $this->daysOld = (int) $daysOld;
        $this->date    = new \DateTime("{$daysOld} days ago", new \DateTimeZone('UTC'));
    }

    /**
     * Get integer for number of days ago to purge data.
     */
    public function getDays(): int
    {
        return $this->daysOld;
    }

    /**
     * Returns a DateTime in UTC for the date to delete records older than the given date.
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Set the number of records purged by the listener.
     *
     * @param string $key
     * @param int    $recordCount
     */
    public function setStat($key, $recordCount, $sql = null, $parameters = []): void
    {
        $this->stats[$key] = (int) $recordCount;

        if ($sql) {
            foreach ($parameters as $paramKey => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $sql = str_replace(":{$paramKey}", (string) $value, $sql);
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
     */
    public function isDryRun(): bool
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

    public function isGdpr(): bool
    {
        return $this->gdpr;
    }
}
