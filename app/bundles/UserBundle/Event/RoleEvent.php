<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\UserBundle\Entity\Role;

/**
 * Class RoleEvent.
 */
class RoleEvent extends CommonEvent
{
    /**
     * @param Role $role
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
     *
     * @param Role $role
     */
    public function setRole(Role $role)
    {
        $this->entity = $role;
    }
}
