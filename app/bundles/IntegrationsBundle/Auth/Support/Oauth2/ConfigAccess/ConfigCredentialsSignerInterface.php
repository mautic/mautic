<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess;

use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;

interface ConfigCredentialsSignerInterface extends AuthConfigInterface
{
    public function getCredentialsSigner(): SignerInterface;
}
