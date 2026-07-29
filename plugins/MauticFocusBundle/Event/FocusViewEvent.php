<?php

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

final class FocusViewEvent extends Event
{
    public function __construct(
        private readonly Stat $stat,
    ) {
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
