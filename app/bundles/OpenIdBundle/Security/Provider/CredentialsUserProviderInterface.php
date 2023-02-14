<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Security\Provider;

use Mautic\OpenIdBundle\DTO\UserCredentials;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

interface CredentialsUserProviderInterface extends UserProviderInterface
{
    public function loadUserByCredentials(UserCredentials $credentials): UserInterface;
}
