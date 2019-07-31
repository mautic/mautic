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
        // @todo
//        $token = $this->createMock(TokenInterface::class);
//        $integration = $this->createMock(Integration::class);
//        $this->tokenPersistence->setIntegration($integration);
//        $this->assertSame($token, $this->tokenPersistence->restoreToken($token));
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
        $integration = $this->createMock(Integration::class);
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
        $token = $this->createMock(TokenInterface::class);
        $this->assertFalse($this->tokenPersistence->hasToken());
        $integration = $this->createMock(Integration::class);
        $this->tokenPersistence->setIntegration($integration);
        $this->tokenPersistence->saveToken($token);
        $this->assertTrue($this->tokenPersistence->hasToken());
    }
}