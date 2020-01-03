<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated; Use Credentials\CredentialsInterface and \Mautic\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface instead
 */
interface AuthorizatorInterface
{
    public function isAuthorized(): bool;

    public function getAccessToken(): string;

    public function getAuthorizationUri(CredentialsInterface $credentials): string;

    public function handleCallback(Request $request): void;
}
