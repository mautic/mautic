<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Event;

use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FocusOpenEvent.
 */
class FocusOpenEvent extends Event
{
    /**
     * @var Focus
     */
    private $focus;

    /**
     * @var Stat
     */
    private $stat;

    /**
     * @param Stat $stat
     */
    public function __construct(Stat $stat)
    {
        $this->stat  = $stat;
        $this->focus = $stat->getFocus();
    }

    /**
     * Returns the Focus entity.
     *
     * @return Focus
     */
    public function getFocus()
    {
        return $this->focus;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }
}
