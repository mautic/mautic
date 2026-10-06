<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\BasicAuth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients using basic auth.
 */
final class HttpFactory implements AuthProviderInterface
{
    public const string NAME = 'basic_auth';

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private array $initializedClients = [];

    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @throws PluginNotConfiguredException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        if (!$credentials instanceof CredentialsInterface) {
            throw new InvalidCredentialsException(sprintf('Credentials must implement the %s interface', CredentialsInterface::class));
        }

        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Username and/or password is missing');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getUsername()])) {
            return $this->initializedClients[$credentials->getUsername()];
        }

        $this->initializedClients[$credentials->getUsername()] = new Client(
            [
                'auth' => [
                    $credentials->getUsername(),
                    $credentials->getPassword(),
                ],
            ]
        );

        return $this->initializedClients[$credentials->getUsername()];
    }

    private function credentialsAreConfigured(CredentialsInterface $credentials): bool
    {
        return $credentials->getUsername() && $credentials->getPassword();
    }
}
