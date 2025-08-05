<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class JobExtendTimeEvent extends Event
{
    public function __construct(private int $throttleSeconds = 60)
    {
    }

    public function getThrottleSeconds(): int
    {
        return $this->throttleSeconds;
    }
}
