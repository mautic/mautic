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
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\IntegrationsBundle\Auth\Provider\TokenPersistence;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotSetException;

class TokenPersistenceTest  extends \PHPUnit_Framework_TestCase
{
    private $encryptionHelper;
    private $integrationEntityRepository;
    private $tokenPersistence;

    public function setUp()
    {
        $this->encryptionHelper = $this->createMock(EncryptionHelper::class);
        $this->integrationEntityRepository = $this->createMock(IntegrationEntityRepository::class);
        $this->tokenPersistence = new TokenPersistence($this->encryptionHelper, $this->integrationEntityRepository);
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

        $finalApiKeys = [
            'access_token'  => $this->encryptionHelper->encrypt($tokenFromApi->getAccessToken()),
            'refresh_token' => $this->encryptionHelper->encrypt($tokenFromApi->getRefreshToken()),
            // @todo
            'expires_at'    => $this->encryptionHelper->encrypt($tokenFromApi->getExpiresAt()),
        ];        

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->once())
            ->method('getApiKeys')
            ->willReturn($apiKeys);
        $integration->expects($this->once())
            ->method('setApiKeys')
            ->with($finalApiKeys);

        $this->encryptionHelper->expects($this->exactly(3))
            ->method('encrypt');
        $this->encryptionHelper->expects($this->exactly(3))
            ->method('decrypt');

        $this->tokenPersistence->setIntegration($integration);

        $newToken = $this->tokenPersistence->restoreToken($tokenFromApi);

        $this->assertSame($tokenFromApi->getAccessToken(), $newToken->getAccessToken());
        $this->assertSame($tokenFromApi->getRefreshToken(), $newToken->getRefreshToken());
//        $this->assertSame($tokenFromApi->getExpiresAt(), $newToken->getExpiresAt());
        
    }

    public function testIntegrationNotSetSaveToken()
    {
        $this->expectException(IntegrationNotSetException::class);

        $token = $this->createMock(TokenInterface::class);
        $this->tokenPersistence->saveToken($token);
    }

    public function testSaveToken()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->encryptionHelper->expects($this->exactly(3))
            ->method('encrypt');

        $integration = $this->createMock(Integration::class);
        $integration->expects($this->once())
            ->method('setApiKeys');
        $this->tokenPersistence->setIntegration($integration);

        $this->assertNull($this->tokenPersistence->saveToken($token));

        $this->assertTrue($this->tokenPersistence->hasToken());
    }

    public function testDeleteToken()
    {
        $token = $this->createMock(TokenInterface::class);
        $integration = $this->createMock(Integration::class);
        $this->tokenPersistence->setIntegration($integration);
        $this->tokenPersistence->saveToken($token);
        $this->assertTrue($this->tokenPersistence->hasToken());

        $this->tokenPersistence->deleteToken();
        $this->assertFalse($this->tokenPersistence->hasToken());
    }

    public function testHasToken()
    {
        $this->assertFalse($this->tokenPersistence->hasToken());

        $token = new RawToken('kajshfddkadsfdw');

        $integration = $this->createMock(Integration::class);
        $this->tokenPersistence->setIntegration($integration);
        $this->tokenPersistence->saveToken($token);
        $this->assertTrue($this->tokenPersistence->hasToken());

        $token = new RawToken();
        $this->tokenPersistence->saveToken($token);
        $this->assertFalse($this->tokenPersistence->hasToken());
    }
}