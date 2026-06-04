<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DTO;

final class PublishStateDateRange
{
    public function __construct(private bool $published, private \DateTimeInterface $fromDate, private ?\DateTimeInterface $toDate = null)
    {
        if ($this->toDate && $this->fromDate > $this->toDate) {
            $this->toDate = null; // Invalid range: make it open-ended when fromDate is after toDate
        }
    }

    public function setToDate(?\DateTimeInterface $toDate): void
    {
        $this->toDate = $toDate;
    }

    public function getFromDate(): \DateTimeInterface
    {
        return $this->fromDate;
    }

    public function getToDate(): ?\DateTimeInterface
    {
        return $this->toDate;
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    /**
     * Null as toDate means the range is open-ended and so the to date is considered as now and/or the future.
     */
    public function happenedWithinRange(\DateTimeInterface $date): bool
    {
        return $date >= $this->fromDate && (null === $this->toDate || $date <= $this->toDate);
    }
}
