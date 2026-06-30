<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class JobExtendTimeEvent extends Event
{
    public function __construct(private readonly int $throttleSeconds = 60)
    {
    }

    public function getThrottleSeconds(): int
    {
        return $this->throttleSeconds;
    }
}
