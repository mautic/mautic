<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\GrantType\GrantTypeInterface;
use kamermans\OAuth2\GrantType\PasswordCredentials;
use kamermans\OAuth2\GrantType\RefreshToken;
use kamermans\OAuth2\OAuth2Middleware;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\ClientCredentialsGrantInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\PasswordCredentialsGrantInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\ScopeInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\StateInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigCredentialsSignerInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenFactoryInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenSignerInterface;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth2 headers.
 * Based on Guzzle OAuth 2.0 Subscriber - kamermans/guzzle-oauth2-subscriber package.
 *
 * @see https://github.com/kamermans/guzzle-oauth2-subscriber
 */
class HttpFactory implements AuthProviderInterface
{
    public const NAME = 'oauth2_two_legged';

    /**
     * @var PasswordCredentialsGrantInterface|ClientCredentialsGrantInterface
     */
    private $credentials;

    /**
     * @var ConfigCredentialsSignerInterface|ConfigTokenPersistenceInterface|ConfigTokenSignerInterface
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
     * @param PasswordCredentialsGrantInterface|ClientCredentialsGrantInterface|AuthCredentialsInterface                                                  $credentials
     * @param ConfigCredentialsSignerInterface|ConfigTokenPersistenceInterface|ConfigTokenSignerInterface|AuthConfigInterface|ConfigTokenFactoryInterface $config
     *
     * @throws PluginNotConfiguredException
     * @throws InvalidCredentialsException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        if (!$this->credentialsAreValid($credentials)) {
            throw new InvalidCredentialsException(sprintf('Credentials must implement either the %s or %s interfaces', PasswordCredentialsGrantInterface::class, ClientCredentialsGrantInterface::class));
        }

        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Authorization URL, client ID or client secret is missing');
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

    private function credentialsAreValid(AuthCredentialsInterface $credentials): bool
    {
        return $credentials instanceof PasswordCredentialsGrantInterface || $credentials instanceof ClientCredentialsGrantInterface;
    }

    /**
     * @param ClientCredentialsGrantInterface|PasswordCredentialsGrantInterface|AuthCredentialsInterface $credentials
     */
    private function credentialsAreConfigured(AuthCredentialsInterface $credentials): bool
    {
        if (empty($credentials->getAuthorizationUrl()) || empty($credentials->getClientId()) || empty($credentials->getClientSecret())) {
            return false;
        }

        if ($credentials instanceof PasswordCredentialsGrantInterface && (empty($credentials->getUsername()) || empty($credentials->getPassword()))) {
            return false;
        }

        return true;
    }

    private function getStackHandler(): HandlerStack
    {
        $reAuthConfig          = $this->getReAuthConfig();
        $accessTokenGrantType  = $this->getGrantType($reAuthConfig);
        $refreshTokenGrantType = new RefreshToken($this->getReAuthClient(), $reAuthConfig);
        $middleware            = new OAuth2Middleware($accessTokenGrantType, $refreshTokenGrantType);

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
                'base_uri' => $this->credentials->getAuthorizationUrl(),
            ]
        );

        return $this->reAuthClient;
    }

    private function getReAuthConfig(): array
    {
        $config = [
            'client_id'     => $this->credentials->getClientId(),
            'client_secret' => $this->credentials->getClientSecret(),
        ];

        if ($this->credentials instanceof ScopeInterface) {
            $config['scope'] = $this->credentials->getScope();
        }

        if ($this->credentials instanceof StateInterface) {
            $config['state'] = $this->credentials->getState();
        }

        if ($this->credentials instanceof ClientCredentialsGrantInterface) {
            return $config;
        }

        $config['username'] = $this->credentials->getUsername();
        $config['password'] = $this->credentials->getPassword();

        return $config;
    }

    private function getGrantType(array $config): GrantTypeInterface
    {
        if ($this->credentials instanceof ClientCredentialsGrantInterface) {
            return new ClientCredentials($this->getReAuthClient(), $config);
        }

        return new PasswordCredentials($this->getReAuthClient(), $config);
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
