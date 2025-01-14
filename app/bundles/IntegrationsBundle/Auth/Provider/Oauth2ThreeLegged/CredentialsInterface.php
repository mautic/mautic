<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

/**
 * @deprecated; use Credentials\CredentialsInterface instead
 */
interface CredentialsInterface extends AuthCredentialsInterface
{
    public function getAuthorizationUrl(): string;

    public function getRequestTokenUrl(): string;

    public function getAccessTokenUrl(): string;

    public function getAuthCallbackUrl(): ?string;

    public function getClientId(): ?string;

    public function getClientSecret(): ?string;

    public function getBearerToken(): ?string;

    public function getAccessToken(): ?string;

    public function getRequestToken(): ?string;
}
