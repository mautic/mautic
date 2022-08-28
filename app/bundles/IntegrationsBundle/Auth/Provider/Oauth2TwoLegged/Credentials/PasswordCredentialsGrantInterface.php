<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface PasswordCredentialsGrantInterface extends AuthCredentialsInterface
{
    public function getAuthorizationUrl(): string;

    public function getClientId(): ?string;

    public function getClientSecret(): ?string;

    public function getUsername(): ?string;

    public function getPassword(): ?string;
}
