<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Event;

use Mautic\PointBundle\Entity\Group;

final class GroupEvent
{
    private Group $entity;

    public function __construct(Group $group)
    {
        $this->entity = $group;
    }

    public function getGroup(): Group
    {
        return $this->entity;
    }

    public function setGroup(Group $group): void
    {
        $this->entity = $group;
    }
}
