<?php

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class FocusViewEvent extends Event
{
    public function __construct(
        private Stat $stat,
    ) {
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
