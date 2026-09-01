<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\PageBundle\Entity\Page;

final class PageBuilderEvent extends BuilderEvent
{
    /**
     * @return Page|null
     */
    public function getPage()
    {
        return $this->entity;
    }
}
