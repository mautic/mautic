<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\User;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;

interface UserFactoryInterface
{
    public function create(UserCredentials $credentials): User;
}
