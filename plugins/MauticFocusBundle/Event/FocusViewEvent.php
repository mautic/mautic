<?php

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Contracts\EventDispatcher\Event;

class FocusViewEvent extends Event
{
<<<<<<< HEAD
    public function __construct(private Stat $stat)
=======
    private \MauticPlugin\MauticFocusBundle\Entity\Stat $stat;

    public function __construct(Stat $stat)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }
}
