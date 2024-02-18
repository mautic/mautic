<?php

namespace Mautic\DynamicContentBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;

class DynamicContentEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(DynamicContent $entity, $isNew = false)
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
