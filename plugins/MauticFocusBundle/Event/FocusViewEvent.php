<?php

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class FocusViewEvent extends Event
{
    public function __construct(
        private readonly Stat $stat,
    ) {
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }
}
