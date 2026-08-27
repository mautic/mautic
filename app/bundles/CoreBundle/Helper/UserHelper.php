<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserHelper
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getUser(bool $nullIfGuest = false): ?User
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
