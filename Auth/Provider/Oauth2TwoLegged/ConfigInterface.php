<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged;


use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface as ClientCredentialsSigner;
use kamermans\OAuth2\Signer\AccessToken\SignerInterface as AccessTokenSigner;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthConfigInterface;

interface ConfigInterface extends AuthConfigInterface
{
    /**
     * @return ClientCredentialsSigner
     */
    public function getClientCredentialsSigner(): ClientCredentialsSigner;

    /**
     * @return AccessTokenSigner
     */
    public function getAccessTokenSigner(): AccessTokenSigner;

    /**
     * @return TokenPersistenceInterface
     */
    public function getAccessTokenPersistence(): TokenPersistenceInterface;
}