<?php

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LoginEvent.
 */
class LoginEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return \Mautic\UserBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
