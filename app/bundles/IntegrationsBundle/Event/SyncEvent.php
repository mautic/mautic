<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Symfony\Component\EventDispatcher\Event;

class SyncEvent extends Event
{
    /**
     * @var string
     */
    private $integrationName;

    /**
     * @var InputOptionsDAO
     */
    private $inputOptionsDAO;

    public function __construct(string $integrationName, InputOptionsDAO $inputOptionsDAO)
    {
        $this->integrationName = $integrationName;
        $this->inputOptionsDAO = $inputOptionsDAO;
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
        return $this->inputOptionsDAO->getStartDateTime();
    }

    public function getToDateTime(): ?\DateTimeInterface
    {
        return $this->inputOptionsDAO->getEndDateTime();
    }

    public function getInputOptions(): InputOptionsDAO
    {
        return $this->inputOptionsDAO;
    }
}
