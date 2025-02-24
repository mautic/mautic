<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserHelper
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * @param bool $nullIfGuest
     *
     * @return User|null
     */
    public function getUser($nullIfGuest = false)
    {
        $user  = null;
        $token = $this->tokenStorage->getToken();

        if (null !== $token) {
            $user = $token->getUser();
        }

        if (!$user instanceof User) {
            if ($nullIfGuest) {
                return null;
            }

            $user = new User(true);
        }

        return $user;
    }
}
