<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MauticSyncFieldsLoadEvent extends Event
{
    public function __construct(
        private string $objectName,
        private array $fields,
    ) {
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $key, string $name): void
    {
        $this->fields[$key] = $name;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }
}
