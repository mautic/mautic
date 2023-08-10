<?php

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class FocusViewEvent.
 */
class FocusViewEvent extends Event
{
    /**
     * @var Stat
     */
    private $stat;

    public function __construct(Stat $stat)
    {
        $this->stat  = $stat;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }
}
