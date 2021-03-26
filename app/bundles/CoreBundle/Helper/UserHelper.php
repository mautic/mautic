<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class IpLookupHelper.
 */
class UserHelper
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * UserHelper constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
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
