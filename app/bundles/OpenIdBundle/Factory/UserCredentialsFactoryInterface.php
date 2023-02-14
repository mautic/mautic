<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Factory;

use Mautic\OpenIdBundle\DTO\UserCredentials;

interface UserCredentialsFactoryInterface
{
    public function create(): UserCredentials;
}
