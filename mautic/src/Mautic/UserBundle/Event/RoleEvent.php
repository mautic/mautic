<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Mautic\UserBundle\Entity\Role;

/**
 * Class RoleEvent
 *
 * @package Mautic\RoleBundle\Event
 */
class RoleEvent extends Event
{
    /**
     * @var \Mautic\UserBundle\Entity\Role
     */
    protected $role;

    /**
     * @var
     */
    protected $isNew;

    /**
     * @param Role $role
     * @param bool $isNew
     */
    public function __construct(Role &$role, $isNew = false)
    {
        $this->role  =& $role;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Role entity
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Returns if a saved role is new or not
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }
}
