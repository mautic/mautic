<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\UserBundle\Entity\User;

final class UserEvent extends CommonEvent
{
    public function __construct(User &$user, bool $isNew = false)
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
     */
    public function setUser(User $user): void
    {
        $this->entity = $user;
    }
}
