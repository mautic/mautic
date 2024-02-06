<?php

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class LoginEvent extends Event
{
    public function __construct(
        private User $user
    ) {
    }

    /**
     * @return \Mautic\UserBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
