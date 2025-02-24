<?php

namespace Mautic\UserBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\UserBundle\Entity\Role;

class RoleEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(Role &$role, $isNew = false)
    {
        $this->entity = &$role;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Role entity.
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->entity;
    }

    /**
     * Sets the Role entity.
     */
    public function setRole(Role $role): void
    {
        $this->entity = $role;
    }
}
