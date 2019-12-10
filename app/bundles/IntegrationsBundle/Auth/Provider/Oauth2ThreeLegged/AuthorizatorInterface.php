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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated; Use Credentials\CredentialsInterface and \MauticPlugin\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface instead
 */
interface AuthorizatorInterface
{
    /**
     * @return bool
     */
    public function isAuthorized(): bool;

    /**
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * @param CredentialsInterface $credentials
     *
     * @return string
     */
    public function getAuthorizationUri(CredentialsInterface $credentials): string;

    /**
     * @param Request $request
     */
    public function handleCallback(Request $request): void;
}
