<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\GrantType\AuthorizationCode;
use kamermans\OAuth2\GrantType\RefreshToken;
use kamermans\OAuth2\OAuth2Middleware;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CodeInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\CredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\RedirectUriInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\Credentials\ScopeInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigCredentialsSignerInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenFactoryInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenSignerInterface;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth2 headers.
 * Based on Guzzle OAuth 2.0 Subscriber - kamermans/guzzle-oauth2-subscriber package.
 *
 * @see https://github.com/kamermans/guzzle-oauth2-subscriber
 */
class HttpFactory implements AuthProviderInterface
{
    public const NAME = 'oauth2_three_legged';

    /**
     * @var CredentialsInterface
     */
    private $credentials;

    /**
     * @var ConfigCredentialsSignerInterface|ConfigTokenPersistenceInterface|ConfigTokenSignerInterface|ConfigTokenFactoryInterface
     */
    private $config;

    /**
     * @var Client
     */
    private $reAuthClient;

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private $initializedClients = [];

    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param AuthCredentialsInterface|CredentialsInterface                                                                   $credentials
     * @param ConfigCredentialsSignerInterface|ConfigTokenPersistenceInterface|ConfigTokenSignerInterface|AuthConfigInterface $config
     *
     * @throws PluginNotConfiguredException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Missing credentials');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getClientId()])) {
            return $this->initializedClients[$credentials->getClientId()];
        }

        $this->credentials = $credentials;
        $this->config      = $config;

        $this->initializedClients[$credentials->getClientId()] = new Client(
            [
                'handler' => $this->getStackHandler(),
                'auth'    => 'oauth',
            ]
        );

        return $this->initializedClients[$credentials->getClientId()];
    }

    protected function credentialsAreConfigured(CredentialsInterface $credentials): bool
    {
        if (empty($credentials->getAuthorizationUrl())) {
            return false;
        }

        if (empty($credentials->getTokenUrl())) {
            return false;
        }

        if (empty($credentials->getClientId())) {
            return false;
        }

        if (empty($credentials->getClientSecret())) {
            return false;
        }

        return true;
    }

    private function getStackHandler(): HandlerStack
    {
        $reAuthConfig          = $this->getReAuthConfig();
        $grantType             = new AuthorizationCode($this->getReAuthClient(), $reAuthConfig);
        $refreshTokenGrantType = new RefreshToken($this->getReAuthClient(), $reAuthConfig);
        $middleware            = new OAuth2Middleware($grantType, $refreshTokenGrantType);

        $this->configureMiddleware($middleware);

        $stack = HandlerStack::create();
        $stack->push($middleware);

        return $stack;
    }

    private function getReAuthClient(): ClientInterface
    {
        if ($this->reAuthClient) {
            return $this->reAuthClient;
        }

        $this->reAuthClient = new Client(
            [
                'base_uri' => $this->credentials->getTokenUrl(),
            ]
        );

        return $this->reAuthClient;
    }

    private function getReAuthConfig(): array
    {
        $config = [
            'client_id'     => $this->credentials->getClientId(),
            'client_secret' => $this->credentials->getClientSecret(),
            'code'          => '',
        ];

        if ($this->credentials instanceof ScopeInterface) {
            $config['scope']  = $this->credentials->getScope();
        }

        if ($this->credentials instanceof RedirectUriInterface) {
            $config['redirect_uri']  = $this->credentials->getRedirectUri();
        }

        if ($this->credentials instanceof CodeInterface) {
            $config['code'] = $this->credentials->getCode();
        }

        return $config;
    }

    private function configureMiddleware(OAuth2Middleware $oauth): void
    {
        if (!$this->config) {
            return;
        }

        if ($this->config instanceof ConfigCredentialsSignerInterface) {
            $oauth->setClientCredentialsSigner($this->config->getCredentialsSigner());
        }

        if ($this->config instanceof ConfigTokenPersistenceInterface) {
            $oauth->setTokenPersistence($this->config->getTokenPersistence());
        }

        if ($this->config instanceof ConfigTokenSignerInterface) {
            $oauth->setAccessTokenSigner($this->config->getTokenSigner());
        }

        if ($this->config instanceof ConfigTokenFactoryInterface) {
            $oauth->setTokenFactory($this->config->getTokenFactory());
        }
    }
}
