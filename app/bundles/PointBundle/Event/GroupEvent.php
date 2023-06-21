<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PointBundle\Entity\Group;

class GroupEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Group &$group, $isNew = false)
    {
        $this->entity = &$group;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Group entity.
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->entity;
    }

    /**
     * Sets the Group entity.
     */
    public function setGroup(Group $group): void
    {
        $this->entity = $group;
    }
}
