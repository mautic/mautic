<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

/**
 * Class FocusEvent.
 */
class FocusEvent extends CommonEvent
{
    /**
     * @param bool|false $isNew
     */
    public function __construct(Focus $focus, $isNew = false)
    {
        $this->entity = $focus;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Focus entity.
     *
     * @return Focus
     */
    public function getFocus()
    {
        return $this->entity;
    }

    /**
     * Sets the Focus entity.
     */
    public function setFocus(Focus $focus)
    {
        $this->entity = $focus;
    }
}
