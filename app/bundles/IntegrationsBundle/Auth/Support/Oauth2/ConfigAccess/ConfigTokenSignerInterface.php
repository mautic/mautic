<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess;

use kamermans\OAuth2\Signer\AccessToken\SignerInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;

interface ConfigTokenSignerInterface extends AuthConfigInterface
{
    public function getTokenSigner(): SignerInterface;
}
