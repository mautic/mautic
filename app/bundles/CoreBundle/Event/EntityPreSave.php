<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

final class EntityPreSave extends CommonEvent
{
    public function __construct(object &$entity, bool $isNew = false)
    {
        $this->entity = &$entity;
        $this->isNew  = $isNew;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
