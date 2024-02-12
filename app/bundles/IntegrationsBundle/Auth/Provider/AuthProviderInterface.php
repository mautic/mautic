<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider;

use GuzzleHttp\ClientInterface;

interface AuthProviderInterface
{
    public function getAuthType(): string;

    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface;
}
