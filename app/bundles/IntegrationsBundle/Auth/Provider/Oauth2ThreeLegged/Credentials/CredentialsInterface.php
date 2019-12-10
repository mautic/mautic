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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials;

use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface CredentialsInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthorizationUrl(): string;

    /**
     * @return string
     */
    public function getTokenUrl(): string;

    /**
     * @return null|string
     */
    public function getClientId(): ?string;

    /**
     * @return null|string
     */
    public function getClientSecret(): ?string;
}
