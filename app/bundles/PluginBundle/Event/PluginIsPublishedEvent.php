<?php

namespace Mautic\PluginBundle\Event;

class PluginIsPublishedEvent extends AbstractPluginIntegrationEvent
{
    private int $value;
    private string $message;
    private bool $canPublish;

    public function __construct(int $value)
    {
        $this->value      = $value;
        $this->canPublish = true;
        $this->message    = '';
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
}
