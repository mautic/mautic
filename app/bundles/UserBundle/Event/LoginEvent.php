<?php

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class LoginEvent.
 */
class LoginEvent extends Event
{
    public function __construct(private User $user)
    {
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
