<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MaintenanceEvent extends Event
{
    private readonly \DateTimeInterface $date;

    private array $stats = [];

    /**
     * @var array
     */
    private $debug = [];
    public function __construct(
        private readonly int $daysOld,
        private readonly bool $dryRun,
        private readonly bool $gdpr,
    ) {
        $this->date    = new \DateTime("{$this->daysOld} days ago", new \DateTimeZone('UTC'));
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
    public function setStat($key, $recordCount, $sql = null, array $parameters = []): void
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

    public function getStats(): array
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
