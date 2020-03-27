<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security;

use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

final class UserTokenSetter implements UserTokenSetterInterface
{
    private $userRepository;
    private $tokenStorage;

    public function __construct(UserRepository $userRepository, TokenStorage $tokenStorage)
    {
        $this->userRepository = $userRepository;
        $this->tokenStorage   = $tokenStorage;
    }

    public function setUser(int $userId): void
    {
        $user  = $this->userRepository->getEntity($userId);
        $token = $this->tokenStorage->getToken() ?? new DummyToken();

        $token->setUser($user);
        $this->tokenStorage->setToken($token);
    }
}
