<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DTO;

use Mautic\CoreBundle\Entity\AuditLog;

final class PublishState
{
    private \DateTimeInterface $dateAdded;
    private ?bool $published                 = null;
    private ?\DateTimeInterface $publishUp   = null;
    private ?\DateTimeInterface $publishDown = null;

    public function setFromAuditLog(AuditLog $auditLog, bool $defaultPublishState): void
    {
        $this->dateAdded = \DateTimeImmutable::createFromInterface($auditLog->getDateAdded());

        if (isset($auditLog->getDetails()['isPublished'][1])) {
            $this->published = $auditLog->getDetails()['isPublished'][1];
        } elseif ('create' === $auditLog->getAction()) {
            // FormEntity is published by default so it doesn't create the change if published when created.
            $this->published = true;
        } elseif (null === $this->published) {
            // The current entity state is the best assumption we can make at this point.
            $this->published = $defaultPublishState;
        } else {
            // keep previous state
        }

        if (isset($auditLog->getDetails()['publishUp'][1])) {
            $this->publishUp = (new \DateTimeImmutable($auditLog->getDetails()['publishUp'][1]))->setTimezone(new \DateTimeZone('UTC'));
        }

        if ($this->publishUp < $this->dateAdded) {
            $this->publishUp = null; // reset if in the past
        }

        if (isset($auditLog->getDetails()['publishDown'][1])) {
            $this->publishDown = (new \DateTimeImmutable($auditLog->getDetails()['publishDown'][1]))->setTimezone(new \DateTimeZone('UTC'));
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
