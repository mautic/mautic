<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO;

class DateRange
{
    private ?\DateTimeInterface $fromDate;

    private ?\DateTimeInterface $toDate;

    public function __construct(?\DateTimeInterface $fromDate, ?\DateTimeInterface $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
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
