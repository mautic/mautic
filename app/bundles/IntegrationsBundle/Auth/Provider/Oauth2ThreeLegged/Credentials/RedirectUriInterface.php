<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials;

interface RedirectUriInterface
{
    public function getRedirectUri(): string;
}
