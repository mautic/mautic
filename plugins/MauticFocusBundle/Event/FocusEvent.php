<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

final class FocusEvent extends CommonEvent
{
    /**
     * @param bool|false $isNew
     */
    public function __construct(Focus $focus, bool $isNew = false)
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
    public function setFocus(Focus $focus): void
    {
        $this->entity = $focus;
    }
}
