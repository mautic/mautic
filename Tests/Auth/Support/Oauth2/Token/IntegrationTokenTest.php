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

namespace MauticPlugin\IntegrationsBundle\Tests\Auth\Persistence;

use MauticPlugin\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationToken;

class IntegrationTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $expires   = time() + 100;
        $extraData = ['foo' => 'bar'];
        $token     = new IntegrationToken('accessToken', 'refreshToken', $expires, $extraData);

        $this->assertEquals('accessToken', $token->getAccessToken());
        $this->assertEquals('refreshToken', $token->getRefreshToken());
        $this->assertEquals($expires, $token->getExpiresAt());
        $this->assertEquals($extraData, $token->getExtraData());
    }

    public function testIsExpired()
    {
        $token = new IntegrationToken('accessToken', 'refreshToken', time() - 100);

        $this->assertTrue($token->isExpired());
    }

    public function testIsNotExpired()
    {
        $token = new IntegrationToken('accessToken', 'refreshToken', time() + 100);

        $this->assertFalse($token->isExpired());
    }
}