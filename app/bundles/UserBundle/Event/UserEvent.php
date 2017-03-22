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
use Mautic\UserBundle\Entity\User;

/**
 * Class UserEvent.
 */
class UserEvent extends CommonEvent
{
    /**
     * @param User $user
     * @param bool $isNew
     */
    public function __construct(User &$user, $isNew = false)
    {
        $this->entity = &$user;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the User entity.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->entity;
    }

    /**
     * Sets the User entity.
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->entity = $user;
    }
}
