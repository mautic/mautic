<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

class PluginIsPublishedEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    private int $value;
    private string $integrationName;
    private string $message;
    private bool $canPublish;

    public function __construct(int $value, string $integrationName)
    {
        $this->value           = $value;
        $this->canPublish      = true;
        $this->message         = '';
        $this->integrationName = $integrationName;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function isCanPublish(): bool
    {
        return $this->canPublish;
    }

    public function setCanPublish(bool $canPublish): void
    {
        $this->canPublish = $canPublish;
    }

    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }
}
