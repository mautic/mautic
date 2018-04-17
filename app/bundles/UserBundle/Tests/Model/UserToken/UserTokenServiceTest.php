<?php

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

/**
 * Class UserTokenServiceTest.
 */
class UserTokenServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $randomHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userTokenRepositoryMock;

    protected function setUp()
    {
        $this->randomHelperMock        = $this->getMockBuilder(RandomHelperInterface::class)->getMock();
        $this->userTokenRepositoryMock = $this->getMockBuilder(UserTokenRepositoryInterface::class)->getMock();
    }

    /**
     * Test UserToken sign.
     *
     * Extra:
     * Tests second attempt for generating signature if not unique signature was generated first time
     */
    public function testSign()
    {
        $signatureLength = 32;
        $randomSignature = 'signature';
        $token           = new UserToken();
        $token->setAuthorizator('test-sign');
        $this->randomHelperMock->expects($this->at(0))
            ->method('generate')
            ->with($signatureLength)
            ->willReturn($randomSignature);
        $this->userTokenRepositoryMock->expects($this->at(0))
            ->method('isSignatureUnique')
            ->with($randomSignature)
            ->willReturn(false); // Test second attempt to get unique signature
        $this->randomHelperMock->expects($this->at(1))
            ->method('generate')
            ->with($signatureLength)
            ->willReturn($randomSignature);
        $this->userTokenRepositoryMock->expects($this->at(1))
            ->method('isSignatureUnique')
            ->with($randomSignature)
            ->willReturn(true); // Ok now
        $userTokenService = $this->getUserTokenService();
        $signedToken      = $userTokenService->sign($token, $signatureLength);
        $this->assertSame($signatureLength, strlen($signedToken->getSecret()));
        $this->assertStringMatchesFormat('^A-Za-z0-9', $signedToken->getSecret());
        $this->assertFalse($signedToken->isOneTimeOnly());
        $this->assertNull($signedToken->getExpiration());
    }

    /**
     * Test verify method.
     */
    public function testVerify()
    {
        $token        = new UserToken();
        $user         = new User();
        $authorizator = 'authorizator';
        $token->setUser($user)
            ->setOneTimeOnly(true)
            ->setExpiration(null)
            ->setAuthorizator($authorizator);
        $this->userTokenRepositoryMock->expects($this->at(0))
            ->method('verify')
            ->with($token)
            ->willReturn(true);
        $userTokenService = $this->getUserTokenService();
        $this->assertTrue($userTokenService->verify($token));
    }

    /**
     * @return UserTokenService
     */
    private function getUserTokenService()
    {
        // Prevent IDE from warning about different expected classes because of mock
        /** @var RandomHelperInterface $randomHelperMock */
        $randomHelperMock = $this->randomHelperMock;
        /** @var UserTokenRepositoryInterface $userTokenRepositoryMock */
        $userTokenRepositoryMock = $this->userTokenRepositoryMock;

        return new UserTokenService(
            $randomHelperMock,
            $userTokenRepositoryMock
        );
    }
}
