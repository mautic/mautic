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

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged;

use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface CredentialsInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthUrl(): string;

    /**
     * @return string|null
     */
    public function getConsumerKey(): ?string;

    /**
     * @return string|null
     */
    public function getConsumerSecret(): ?string;

    /**
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * @return string|null
     */
    public function getTokenSecret(): ?string;
}
