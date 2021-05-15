<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Model\UserToken;

use Mautic\CoreBundle\Helper\RandomHelper\RandomHelperInterface;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Entity\UserTokenRepositoryInterface;
use Mautic\UserBundle\Model\UserToken\UserTokenService;
use PHPUnit\Framework\MockObject\MockObject;

class UserTokenServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|RandomHelperInterface
     */
    private $randomHelperMock;

    /**
     * @var MockObject|UserTokenRepositoryInterface
     */
    private $userTokenRepositoryMock;

    protected function setUp(): void
    {
        $this->randomHelperMock        = $this->getMockBuilder(RandomHelperInterface::class)->getMock();
        $this->userTokenRepositoryMock = $this->getMockBuilder(UserTokenRepositoryInterface::class)->getMock();
    }

    /**
     * Tests second attempt for generating secret if not unique secret was generated first time.
     */
    public function testGenerateSecret(): void
    {
        $secretLength    = 6;
        $randomSecret    = 'secret';
        $token           = new UserToken();
        $token->setAuthorizator('test-secret');

        $this->randomHelperMock->expects($this->exactly(2))
            ->method('generate')
            ->with($secretLength)
            ->willReturn($randomSecret);

        $this->userTokenRepositoryMock->expects($this->exactly(2))
            ->method('isSecretUnique')
            ->with($randomSecret)
            ->willReturnOnConsecutiveCalls(
                false, // Test second attempt to get unique secret
                true // Ok now
            );

        $userTokenService = $this->getUserTokenService();
        $secretToken      = $userTokenService->generateSecret($token, $secretLength);
        $this->assertSame($randomSecret, $secretToken->getSecret());
        $this->assertTrue($secretToken->isOneTimeOnly());
        $this->assertNull($secretToken->getExpiration());
    }

    public function testVerify(): void
    {
        $token        = new UserToken();
        $user         = new User();
        $authorizator = 'authorizator';
        $token->setUser($user)
            ->setOneTimeOnly(true)
            ->setExpiration(null)
            ->setAuthorizator($authorizator);

        $this->userTokenRepositoryMock->expects($this->once())
            ->method('verify')
            ->with($token)
            ->willReturn(true);

        $this->assertTrue($this->getUserTokenService()->verify($token));
    }

    private function getUserTokenService(): UserTokenService
    {
        return new UserTokenService(
            $this->randomHelperMock,
            $this->userTokenRepositoryMock
        );
    }
}
