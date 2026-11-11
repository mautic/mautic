<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider;

use GuzzleHttp\ClientInterface;
use Mautic\IntegrationsBundle\Auth\Provider\BasicAuth\CredentialsInterface;

interface AuthProviderInterface
{
    public function getAuthType(): string;

    public function getClient(CredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface;
}
