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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged;

use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface CredentialsInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthUrl(): string;

    /**
     * @return null|string
     */
    public function getConsumerKey(): ?string;

    /**
     * @return null|string
     */
    public function getConsumerSecret(): ?string;

    /**
     * @return null|string
     */
    public function getToken(): ?string;

    /**
     * @return null|string
     */
    public function getTokenSecret(): ?string;
}
