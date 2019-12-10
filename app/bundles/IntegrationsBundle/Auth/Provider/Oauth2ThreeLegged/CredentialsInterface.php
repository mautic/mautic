<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

/**
 * @deprecated; use Credentials\CredentialsInterface instead
 */
interface CredentialsInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthorizationUrl(): string;

    /**
     * @return string
     */
    public function getRequestTokenUrl(): string;

    /**
     * @return string
     */
    public function getAccessTokenUrl(): string;

    /**
     * @return null|string
     */
    public function getAuthCallbackUrl(): ?string;

    /**
     * @return null|string
     */
    public function getClientId(): ?string;

    /**
     * @return null|string
     */
    public function getClientSecret(): ?string;

    /**
     * @return null|string
     */
    public function getBearerToken(): ?string;

    /**
     * @return null|string
     */
    public function getAccessToken(): ?string;

    /**
     * @return null|string
     */
    public function getRequestToken(): ?string;
}
