<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface CredentialsUserProviderInterface extends UserProviderInterface
{
    public function loadUserByCredentials(UserCredentials $credentials): UserInterface;
}
