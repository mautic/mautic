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

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Support\Oauth2\Token;

use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\IntegrationToken;
use PHPUnit\Framework\TestCase;

class IntegrationTokenTest extends TestCase
{
    public function testGetters(): void
    {
        $expires   = time() + 100;
        $extraData = ['foo' => 'bar'];
        $token     = new IntegrationToken('accessToken', 'refreshToken', $expires, $extraData);

        $this->assertEquals('accessToken', $token->getAccessToken());
        $this->assertEquals('refreshToken', $token->getRefreshToken());
        $this->assertEquals($expires, $token->getExpiresAt());
        $this->assertEquals($extraData, $token->getExtraData());
    }

    public function testIsExpired(): void
    {
        $token = new IntegrationToken('accessToken', 'refreshToken', time() - 100);

        $this->assertTrue($token->isExpired());
    }

    public function testIsNotExpired(): void
    {
        $token = new IntegrationToken('accessToken', 'refreshToken', time() + 100);

        $this->assertFalse($token->isExpired());
    }
}
