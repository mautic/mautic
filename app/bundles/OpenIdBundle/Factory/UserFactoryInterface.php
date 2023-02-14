<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Factory;

use Mautic\OpenIdBundle\DTO\UserCredentials;
use Mautic\UserBundle\Entity\User;

interface UserFactoryInterface
{
    public function create(UserCredentials $credentials): User;
}
