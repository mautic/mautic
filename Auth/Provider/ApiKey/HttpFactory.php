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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients using basic auth
 */
class HttpFactory implements AuthProviderInterface
{
    const NAME = 'api_key';

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private $initializedClients = [];

    /**
     * @var HeaderCredentialsInterface|ParameterCredentialsInterface
     */
    private $credentials;

    /**
     * @return string
     */
    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param HeaderCredentialsInterface|ParameterCredentialsInterface $credentials
     *
     * @return ClientInterface
     * @throws PluginNotConfiguredException
     */
    public function getClient($credentials): ClientInterface
    {
        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Username or password is missing');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getKeyName()])) {
            return $this->initializedClients[$credentials->getKeyName()];
        }

        $this->credentials = $credentials;

        if ($credentials instanceof HeaderCredentialsInterface) {
            return $this->initializedClients[$credentials->getKeyName()] = $this->getHeaderClient();
        }

        return $this->initializedClients[$credentials->getKeyName()] = $this->getParameterClient();
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials): bool
    {
        return !empty($credentials->getApiKey());
    }

    /**
     * @return ClientInterface
     */
    private function getHeaderClient(): ClientInterface
    {
        return new Client(
            [
                'headers' => [$this->credentials->getKeyName() => $this->credentials->getApiKey()]
            ]
        );
    }

    /**
     * @return ClientInterface
     */
    private function getParameterClient(): ClientInterface
    {
        $handler = new HandlerStack();
        $handler->setHandler(new CurlHandler());

        $handler->unshift(
            Middleware::mapRequest(
                function (Request $request) {
                    return $request->withUri(
                        Uri::withQueryValue($request->getUri(), $this->credentials->getKeyName(), $this->credentials->getApiKey())
                    );
                }
            )
        );

        return new Client(
            [
                'handler' => $handler
            ]
        );
    }
}
