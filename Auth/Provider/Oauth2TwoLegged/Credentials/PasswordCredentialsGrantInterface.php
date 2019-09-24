<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials;

use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface PasswordCredentialsGrantInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthorizationUrl(): string;

    /**
     * @return null|string
     */
    public function getClientId(): ?string;

    /**
     * @return null|string
     */
    public function getClientSecret(): ?string;

    /**
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * @return string|null
     */
    public function getPassword(): ?string;
}
