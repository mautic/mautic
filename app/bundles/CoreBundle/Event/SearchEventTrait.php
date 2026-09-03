<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

trait SearchEventTrait
{
    public function getContext(): string
    {
        return $this->context;
    }

    public function checkContext(string $context): bool
    {
        return $this->context === $context;
    }
}
