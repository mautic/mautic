<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO;

class DateRange
{
    public function __construct(
        private ?\DateTimeInterface $fromDate,
        private ?\DateTimeInterface $toDate,
    ) {
    }

    /**
     * Get the value of fromDate.
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * Get the value of toDate.
     */
    public function getToDate()
    {
        return $this->toDate;
    }
}
