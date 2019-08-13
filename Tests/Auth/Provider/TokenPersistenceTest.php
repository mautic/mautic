<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Auth\Provider;

use kamermans\OAuth2\Token\RawToken;
use kamermans\OAuth2\Token\RawTokenFactory;
use kamermans\OAuth2\Token\TokenInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Auth\Provider\TokenPersistence;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotSetException;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;

class TokenPersistenceTest  extends \PHPUnit_Framework_TestCase
{
    private $integrationsHelper;
    private $tokenPersistence;

    public function setUp()
    {
        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->tokenPersistence = new TokenPersistence($this->integrationsHelper);
        parent::setUp();
    }

    public function testIntegrationNotSetRestoreToken()
    {
        $this->expectException(IntegrationNotSetException::class);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenPersistence->restoreToken($token);
    }

    public function testRestoreToken()
    {
        $oldAccessToken = 'old_access_token';
        $oldRefreshToken = 'old_refresh_token';
        $oldExpiresAt = 3600;
        $apiKeys = [
            'access_token' => $oldAccessToken,
            'refresh_token' => $oldRefreshToken,
            'expires_at' => $oldExpiresAt,
        ];

        $apiAccessToken = 'api_access_token';
        $apiRefreshToken = 'api_refresh_token';
        $apiExpiresAt = 3600;

        $factory = new RawTokenFactory();
        $tokenFromApi = $factory([
            'access_token' => $apiAccessToken,
            'refresh_token' => $apiRefreshToken,
            'expires_in' => $apiExpiresAt,
        ]);

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->once())
            ->method('getApiKeys')
            ->willReturn($apiKeys);

        $this->tokenPersistence->setIntegration($integration);

        $newToken = $this->tokenPersistence->restoreToken($tokenFromApi);

        $this->assertSame($tokenFromApi->getAccessToken(), $newToken->getAccessToken());
        $this->assertSame($tokenFromApi->getRefreshToken(), $newToken->getRefreshToken());
        $this->assertSame($tokenFromApi->getExpiresAt(), $newToken->getExpiresAt());
    }

    public function testIntegrationNotSetSaveToken()
    {
        $this->expectException(IntegrationNotSetException::class);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenPersistence->saveToken($token);
    }

    public function testSaveToken()
    {
        $oldApiKeys = [
            'access_token' => 'old_access_token',
            'something' => 'something',
        ];

        $newApiKeys = [
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => '0',
        ];

        $token = new RawToken($newApiKeys['access_token'], $newApiKeys['refresh_token'], $newApiKeys['expires_at']);

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->at(0))
            ->method('getApiKeys')
            ->willReturn($oldApiKeys);
        $newApiKeys = array_merge($oldApiKeys, $newApiKeys);
        $integration->expects($this->once())
            ->method('setApiKeys')
            ->with($newApiKeys);
        $integration->expects($this->at(2))
            ->method('getApiKeys')
            ->willReturn($newApiKeys);
        $this->tokenPersistence->setIntegration($integration);

        $this->integrationsHelper->expects($this->once())
            ->method('saveIntegrationConfiguration');

        $this->tokenPersistence->saveToken($token);

        $this->assertTrue($this->tokenPersistence->hasToken());
    }

    public function testIntegrationNotSetDeleteToken()
    {
        $this->expectException(IntegrationNotSetException::class);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenPersistence->saveToken($token);
    }

    public function testDeleteToken()
    {
        $accessToken = 'lsadkjfasd';
        $refreshToken = 'kjsahfkas';
        $expiresAt = 5465465;
        $token = new RawToken($accessToken,$refreshToken, $expiresAt);
        $expected = [
            'leaveMe' => 'something',
        ];
        $apiKeys = array_merge(
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
            ],
            $expected
        );

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->at(0))
            ->method('getApiKeys')
            ->willReturn($apiKeys);
        $integration->expects($this->at(2))
            ->method('getApiKeys')
            ->willReturn($apiKeys);
        $integration->expects($this->at(3))
            ->method('setApiKeys')
            ->with([]);

        $this->tokenPersistence->setIntegration($integration);

        $this->integrationsHelper->expects($this->exactly(2))
            ->method('saveIntegrationConfiguration');
        $this->tokenPersistence->saveToken($token);

        $this->assertTrue($this->tokenPersistence->hasToken());

        $this->tokenPersistence->deleteToken();


        $this->assertFalse($this->tokenPersistence->hasToken());
    }

    public function testHasToken()
    {
        $accessToken = 'sjak';

        $oldApiKeys = [];

        $token = new RawToken($accessToken);

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->at(3))
            ->method('getApiKeys')
            ->willReturn($oldApiKeys);
        $integration->expects($this->at(2))
            ->method('getApiKeys')
            ->willReturn(['access_token' => $accessToken]);
        $integration->expects($this->at(3))
            ->method('getApiKeys')
            ->willReturn(['access_token' => $accessToken]);

        $this->tokenPersistence->setIntegration($integration);
        $this->assertFalse($this->tokenPersistence->hasToken());
        $this->tokenPersistence->saveToken($token);
        $this->assertTrue($this->tokenPersistence->hasToken());

        $token = new RawToken();
        $this->tokenPersistence->saveToken($token);
        $this->assertFalse($this->tokenPersistence->hasToken());
    }
}