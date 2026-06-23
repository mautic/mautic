<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UpgradeEvent extends Event
{
    public function __construct(
        protected array $status,
    ) {
    }

    public function getStatus(): array
    {
        return $this->status;
    }

    public function isSuccessful(): bool
    {
        if (array_key_exists('success', $this->status)) {
            return (bool) $this->status['success'];
        }

        return false;
    }
}
