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

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth1aThreeLegged;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface CredentialsInterface extends AuthCredentialsInterface
{
    public function getAuthorizationUrl(): string;

    public function getRequestTokenUrl(): string;

    public function getAccessTokenUrl(): string;

    public function getAuthCallbackUrl(): ?string;

    public function getConsumerId(): ?string;

    public function getConsumerSecret(): ?string;

    public function getAccessToken(): ?string;

    public function getRequestToken(): ?string;
}
