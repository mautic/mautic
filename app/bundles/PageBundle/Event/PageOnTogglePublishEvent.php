<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

final class PageOnTogglePublishEvent extends CommonEvent
{
    public function __construct(Page $page, bool $isNew = false)
    {
        $this->entity = $page;
        $this->isNew  = $isNew;
    }

    public function getPage(): Page
    {
        return $this->entity;
    }

    public function setPage(Page $page): void
    {
        $this->entity = $page;
    }
}
