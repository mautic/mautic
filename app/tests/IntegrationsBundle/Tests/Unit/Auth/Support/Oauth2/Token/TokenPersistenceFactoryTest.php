<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Support\Oauth2\Token;

use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

final class TokenPersistenceFactoryTest extends TestCase
{
    private Integration $integration;

    protected function setup(): void
    {
        $this->integration        = new Integration();
    }

    public function testCreate(): void
    {
        $accessToken  = 'access_token';
        $refreshToken = 'refresh_token';
        $expiresAt    = 10;
        $apiKeys      = [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => $expiresAt,
        ];

        $this->integration->setApiKeys($apiKeys);

        $factory          = new TokenPersistenceFactory($this->createStub(IntegrationsHelper::class));
        $tokenPersistence = $factory->create($this->integration);
        $this->assertTrue($tokenPersistence->hasToken());
    }

    public function testCreateWithInvalidToken(): void
    {
        $accessToken  = null;
        $refreshToken = 'refresh_token';
        $expiresAt    = 10;
        $apiKeys      = [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => $expiresAt,
        ];

        $this->integration->setApiKeys($apiKeys);

        $factory          = new TokenPersistenceFactory($this->createStub(IntegrationsHelper::class));
        $tokenPersistence = $factory->create($this->integration);
        $this->assertFalse($tokenPersistence->hasToken());
    }
}
