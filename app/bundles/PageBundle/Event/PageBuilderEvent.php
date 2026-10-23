<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * @extends BuilderEvent<Page>
 */
final class PageBuilderEvent extends BuilderEvent
{
    public function getPage(): ?Page
    {
        return $this->entity;
    }
}
