<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;

interface UserCredentialsFactoryInterface
{
    public function create(): UserCredentials;
}
