<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface RefreshTokenInterface extends AuthCredentialsInterface
{
    public function getRefreshToken(): ?string;
}
