<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Support\Oauth2\Token;

use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationToken;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationTokenFactory;
use PHPUnit\Framework\TestCase;

class IntegrationTokenFactoryTest extends TestCase
{
    public function testTokenGeneratedWithExpires(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires_in'    => 10,
        ];

        $token = $factory($data);

        $this->assertEquals($data['access_token'], $token->getAccessToken());
        $this->assertEquals($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
    }

    public function testTokenGeneratedWithExpiresAt(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires_at'    => 10,
        ];

        $token = $factory($data);

        $this->assertEquals($data['access_token'], $token->getAccessToken());
        $this->assertEquals($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
    }

    public function testTokenGeneratedWithPreviousRefreshToken(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token' => '123',
            'expires_at'   => 10,
        ];

        $previousToken = new IntegrationToken('789', '456');
        $token         = $factory($data, $previousToken);

        $this->assertEquals($data['access_token'], $token->getAccessToken());
        $this->assertEquals($previousToken->getRefreshToken(), $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
    }

    public function testTokenGeneratedWithExtraData(): void
    {
        $factory = new IntegrationTokenFactory(['foo']);
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires_at'    => 10,
            'foo'           => 'bar',
            'bar'           => 'foo',
        ];

        $token = $factory($data);

        $this->assertEquals($data['access_token'], $token->getAccessToken());
        $this->assertEquals($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertEquals(['foo' => 'bar'], $token->getExtraData());
    }
}
