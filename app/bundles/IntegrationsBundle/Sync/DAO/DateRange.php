<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO;

class DateRange
{
    public function __construct(
        private readonly ?\DateTimeInterface $fromDate,
        private readonly ?\DateTimeInterface $toDate,
    ) {
    }

    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->fromDate;
    }

    public function getToDate(): ?\DateTimeInterface
    {
        return $this->toDate;
    }
}
