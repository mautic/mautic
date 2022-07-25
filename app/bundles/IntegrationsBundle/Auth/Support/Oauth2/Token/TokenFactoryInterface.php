<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token;

use kamermans\OAuth2\Token\TokenInterface;

interface TokenFactoryInterface
{
    public function __invoke(array $data, ?TokenInterface $previousToken = null): IntegrationToken;
}
