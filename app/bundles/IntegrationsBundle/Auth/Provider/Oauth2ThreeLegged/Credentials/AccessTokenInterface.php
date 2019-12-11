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

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface AccessTokenInterface extends AuthCredentialsInterface
{
    /**
     * @return string|null
     */
    public function getAccessToken(): ?string;

    /**
     * @return \DateTimeImmutable|null
     */
    public function getAccessTokenExpiry(): ?\DateTimeImmutable;
}
