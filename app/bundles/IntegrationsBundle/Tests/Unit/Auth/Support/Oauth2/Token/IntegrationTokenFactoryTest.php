<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Support\Oauth2\Token;

use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationToken;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationTokenFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('time-sensitive')]
final class IntegrationTokenFactoryTest extends TestCase
{
    public function testTokenGeneratedWithExpiresIn(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires_in'    => 10,
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame(time() + 10, $token->getExpiresAt());
    }

    public function testTokenGeneratedWithExpiresAt(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires_at'    => time() + 10,
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame($data['expires_at'], $token->getExpiresAt());
    }

    public function testTokenGeneratedWithExpires(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'expires'       => 10,
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame(time() + 10, $token->getExpiresAt());
    }

    public function testTokenGeneratedWithDefaultExpires(): void
    {
        $factory = new IntegrationTokenFactory([], 100);
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame(time() + 100, $token->getExpiresAt());
    }

    public function testTokenGeneratedWithUnexpiredTokenByDefault(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame(0, $token->getExpiresAt());
    }

    public function testTokenGeneratedWithPreviousRefreshToken(): void
    {
        $factory = new IntegrationTokenFactory();
        $data    = [
            'access_token' => '123',
        ];

        $previousToken = new IntegrationToken('789', '456');
        $token         = $factory($data, $previousToken);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($previousToken->getRefreshToken(), $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
    }

    public function testTokenGeneratedWithExtraData(): void
    {
        $factory = new IntegrationTokenFactory(['foo']);
        $data    = [
            'access_token'  => '123',
            'refresh_token' => '456',
            'foo'           => 'bar',
            'bar'           => 'foo',
        ];

        $token = $factory($data);

        $this->assertSame($data['access_token'], $token->getAccessToken());
        $this->assertSame($data['refresh_token'], $token->getRefreshToken());
        $this->assertFalse($token->isExpired());
        $this->assertSame(['foo' => 'bar'], $token->getExtraData());
    }
}
