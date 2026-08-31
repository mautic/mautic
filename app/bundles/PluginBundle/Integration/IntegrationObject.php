<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Integration;

final readonly class IntegrationObject
{
    public function __construct(
        private string $type,
        private string $internalType,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getInternalType(): string
    {
        return $this->internalType;
    }
}
