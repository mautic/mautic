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

namespace Mautic\IntegrationsBundle\Auth\Provider;

use GuzzleHttp\ClientInterface;

interface AuthProviderInterface
{
    public function getAuthType(): string;

    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface;
}
