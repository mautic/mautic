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

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use GuzzleHttp\ClientInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth2 headers.
 * Based on Guzzle OAuth 2.0 Subscriber - kamermans/guzzle-oauth2-subscriber package.
 *
 * @see https://github.com/kamermans/guzzle-oauth2-subscriber
 *
 * @deprecated; use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory
 */
abstract class AbstractClientFactory implements AuthProviderInterface
{
    const NAME = 'oauth2_three_legged';

    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param AuthCredentialsInterface|CredentialsInterface $credentials
     *
     * @throws PluginNotConfiguredException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Oauth2 credentials are not configured');
        }

        return $this->buildClient($credentials);
    }

    abstract protected function buildClient(CredentialsInterface $credentials): ClientInterface;

    abstract protected function credentialsAreConfigured(CredentialsInterface $credentials): bool;
}
