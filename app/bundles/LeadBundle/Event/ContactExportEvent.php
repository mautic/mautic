<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ContactExportEvent extends Event
{
    /**
     * @param array<string|int, int|string|array<string, mixed>> $args
     */
    public function __construct(
        private array $args,
        private string $object,
    ) {
    }

    /**
     * @return array<string, string|array<string, mixed>>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    public function getObject(): string
    {
        return $this->object;
    }
}
