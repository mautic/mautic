<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\BasicAuth;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface CredentialsInterface extends AuthCredentialsInterface
{
    public function getUsername(): ?string;

    public function getPassword(): ?string;
}
