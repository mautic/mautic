<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageEvent.
 */
class PageEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Page $page, $isNew = false)
    {
        $this->entity = $page;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Page entity.
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->entity;
    }

    /**
     * Sets the Page entity.
     */
    public function setPage(Page $page)
    {
        $this->entity = $page;
    }
}
