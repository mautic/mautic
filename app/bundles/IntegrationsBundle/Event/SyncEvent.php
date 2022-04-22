<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SyncEvent extends Event
{
    /** @var string */
    private $integrationName;
    /**
     * @var \DateTimeInterface|null
     */
    private $fromDateTime;
    /**
     * @var \DateTimeInterface|null
     */
    private $toDateTime;

    public function __construct(string $integrationName, ?\DateTimeInterface $fromDateTime = null, ?\DateTimeInterface $toDateTime = null)
    {
        $this->integrationName = $integrationName;
        $this->fromDateTime    = $fromDateTime;
        $this->toDateTime      = $toDateTime;
    }

    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }

    public function isIntegration(string $integrationName): bool
    {
        return $this->getIntegrationName() === $integrationName;
    }

    public function getFromDateTime(): ?\DateTimeInterface
    {
        return $this->fromDateTime;
    }

    public function getToDateTime(): ?\DateTimeInterface
    {
        return $this->toDateTime;
    }
}
