<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;

final class DynamicContentEvent extends CommonEvent
{
    public function __construct(DynamicContent $entity, bool $isNew = false)
    {
        $this->entity = $entity;
        $this->isNew  = $isNew;
    }

    /**
     * @return DynamicContent
     */
    public function getDynamicContent()
    {
        return $this->entity;
    }

    public function setDynamicContent(DynamicContent $entity): void
    {
        $this->entity = $entity;
    }
}
