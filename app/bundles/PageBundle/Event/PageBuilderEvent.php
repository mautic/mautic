<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageBuilderEvent.
 */
class PageBuilderEvent extends BuilderEvent
{
    /**
     * @return Page|null
     */
    public function getPage()
    {
        return $this->entity;
    }
}
