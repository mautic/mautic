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

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

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
     * @return string|null
     */
    public function getAuthCallbackUrl(): ?string;

    /**
     * @return string|null
     */
    public function getClientId(): ?string;

    /**
     * @return string|null
     */
    public function getClientSecret(): ?string;

    /**
     * @return string|null
     */
    public function getBearerToken(): ?string;

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string;

    /**
     * @return string|null
     */
    public function getRequestToken(): ?string;
}
