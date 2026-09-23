<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DTO;

use Mautic\CoreBundle\Entity\AuditLog;

final class PublishState
{
    private \DateTimeInterface $dateAdded;

    private bool $published;

    private ?\DateTimeInterface $publishUp;

    private ?\DateTimeInterface $publishDown;

    public function __construct(AuditLog $auditLog, bool $defaultPublishState, ?self $previous = null)
    {
        $this->dateAdded   = \DateTimeImmutable::createFromInterface($auditLog->getDateAdded());
        $this->publishUp   = $previous?->publishUp;
        $this->publishDown = $previous?->publishDown;

        $details = $auditLog->getDetails();

        if (isset($details['isPublished'][1])) {
            $this->published = $details['isPublished'][1];
        } elseif ('create' === $auditLog->getAction()) {
            // FormEntity is published by default so it doesn't create the change if published when created.
            $this->published = true;
        } else {
            // keep the previous state, or fall back to the current entity state
            $this->published = null !== $previous ? $previous->published : $defaultPublishState;
        }

        if (isset($details['publishUp'][1])) {
            $this->publishUp = new \DateTimeImmutable($details['publishUp'][1])->setTimezone(new \DateTimeZone('UTC'));
        }

        if ($this->publishUp < $this->dateAdded) {
            $this->publishUp = null; // reset if in the past
        }

        if (isset($details['publishDown'][1])) {
            $this->publishDown = new \DateTimeImmutable($details['publishDown'][1])->setTimezone(new \DateTimeZone('UTC'));
        }

        if ($this->publishDown < $this->dateAdded) {
            $this->publishDown = null; // reset if in the past
        }
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function getPublishUp(): ?\DateTimeInterface
    {
        return $this->publishUp;
    }

    public function getPublishDown(): ?\DateTimeInterface
    {
        return $this->publishDown;
    }

    public function getDateAdded(): \DateTimeInterface
    {
        return $this->dateAdded;
    }
}
