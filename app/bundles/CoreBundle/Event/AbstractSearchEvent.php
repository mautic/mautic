<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractSearchEvent extends Event
{
    protected string $context;

    public function getContext(): string
    {
        return $this->context;
    }

    public function checkContext(string $context): bool
    {
        return $this->context === $context;
    }
}
