<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials;

interface StateInterface
{
    public function getState(): ?string;
}
