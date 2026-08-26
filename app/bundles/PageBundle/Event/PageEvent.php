<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

final class PageEvent extends CommonEvent
{
    public function __construct(Page $page, bool $isNew = false)
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
    public function setPage(Page $page): void
    {
        $this->entity = $page;
    }
}
