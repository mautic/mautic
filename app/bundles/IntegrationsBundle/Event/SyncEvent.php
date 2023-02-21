<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Symfony\Component\EventDispatcher\Event;

class SyncEvent extends Event
{
    private InputOptionsDAO $inputOptionsDAO;

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

    public function __construct(InputOptionsDAO $inputOptionsDAO)
    {
        $this->inputOptionsDAO = $inputOptionsDAO;
        $this->integrationName = $this->inputOptionsDAO->getIntegration();
        $this->fromDateTime    = $this->inputOptionsDAO->getStartDateTime();
        $this->toDateTime      = $this->inputOptionsDAO->getEndDateTime();
    }

    public function getInputOptionsDAO(): InputOptionsDAO
    {
        return $this->inputOptionsDAO;
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
