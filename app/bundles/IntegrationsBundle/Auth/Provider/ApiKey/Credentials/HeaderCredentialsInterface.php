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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\Credentials;

use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;

interface HeaderCredentialsInterface extends AuthCredentialsInterface
{
    /**
     * @return string
     */
    public function getKeyName(): string;

    /**
     * @return null|string
     */
    public function getApiKey(): ?string;
}
