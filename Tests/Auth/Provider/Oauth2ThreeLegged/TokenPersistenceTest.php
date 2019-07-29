<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Auth\Provider\Oauth2ThreeLegged;

use kamermans\OAuth2\Token\TokenInterface;
use MauticPlugin\SalesforceBundle\Connection\TokenPersistence;

class TokenPersistenceTest  extends \PHPUnit_Framework_TestCase
{
    private $token;
    private $tokenPersistence;

    public function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenPersistence = new TokenPersistence();
        parent::setUp();
    }

    public function testRestoreToken()
    {
        $this->assertSame($this->token, $this->tokenPersistence->restoreToken($this->token));
    }

    public function testSaveToken()
    {
        $this->assertNull($this->tokenPersistence->saveToken($this->token));
        $this->assertTrue($this->tokenPersistence->hasToken());
    }

    public function testDeleteToken()
    {
        $this->tokenPersistence->saveToken($this->token);
        $this->assertTrue($this->tokenPersistence->hasToken());
        $this->assertNull($this->tokenPersistence->deleteToken());
        $this->assertFalse($this->tokenPersistence->hasToken());
    }

    public function testHasToken()
    {
        $this->assertFalse($this->tokenPersistence->hasToken());
        $this->tokenPersistence->saveToken($this->token);
        $this->assertTrue($this->tokenPersistence->hasToken());
    }
}