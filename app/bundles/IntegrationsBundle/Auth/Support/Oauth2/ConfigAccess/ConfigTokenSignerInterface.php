<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess;

use kamermans\OAuth2\Signer\AccessToken\SignerInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;

interface ConfigTokenSignerInterface extends AuthConfigInterface
{
    public function getTokenSigner(): SignerInterface;
}
