<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\ApiKey\Credentials;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface HeaderCredentialsInterface extends AuthCredentialsInterface
{
    public function getKeyName(): string;

    public function getApiKey(): ?string;
}
