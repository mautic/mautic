<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security;

use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class UserTokenSetter implements UserTokenSetterInterface
{
    public function __construct(private UserModel $userModel, private TokenStorageInterface $tokenStorage)
    {
    }

    public function setUser(int $userId): void
    {
        $user  = $this->userModel->getEntity($userId);
        $token = $this->tokenStorage->getToken() ?? new DummyToken();

        $token->setUser($user);
        $this->tokenStorage->setToken($token);
    }
}
