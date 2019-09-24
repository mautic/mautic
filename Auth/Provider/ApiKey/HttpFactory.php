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
use MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\HeaderCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\ApiKey\Credentials\ParameterCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Exception\InvalidCredentialsException;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients using basic auth.
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
     * @param HeaderCredentialsInterface|ParameterCredentialsInterface|AuthCredentialsInterface $credentials
     * @param AuthConfigInterface|null                                                          $config
     *
     * @return ClientInterface
     *
     * @throws PluginNotConfiguredException
     * @throws InvalidCredentialsException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        if (!$this->credentialsAreValid($credentials)) {
            throw new InvalidCredentialsException(
                sprintf(
                    'Credentials must implement either the %s or %s interfaces',
                    HeaderCredentialsInterface::class,
                    ParameterCredentialsInterface::class
                )
            );
        }

        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('API key is missing');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getKeyName()])) {
            return $this->initializedClients[$credentials->getKeyName()];
        }

        $this->credentials = $credentials;

        if ($credentials instanceof HeaderCredentialsInterface) {
            $this->initializedClients[$credentials->getKeyName()] = $this->getHeaderClient();

            return $this->initializedClients[$credentials->getKeyName()];
        }

        $this->initializedClients[$credentials->getKeyName()] = $this->getParameterClient();

        return $this->initializedClients[$credentials->getKeyName()];
    }

    /**
     * @param AuthCredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreValid(AuthCredentialsInterface $credentials): bool
    {
        return $credentials instanceof HeaderCredentialsInterface || $credentials instanceof ParameterCredentialsInterface;
    }

    /**
     * @param HeaderCredentialsInterface|ParameterCredentialsInterface|AuthCredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreConfigured(AuthCredentialsInterface $credentials): bool
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
                'headers' => [$this->credentials->getKeyName() => $this->credentials->getApiKey()],
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
                'handler' => $handler,
            ]
        );
    }
}
