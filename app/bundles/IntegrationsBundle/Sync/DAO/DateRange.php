<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO;

final readonly class DateRange
{
    public function __construct(
        private ?\DateTimeInterface $fromDate,
        private ?\DateTimeInterface $toDate,
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
