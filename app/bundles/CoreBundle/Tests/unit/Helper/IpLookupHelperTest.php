<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class IpLookupHelperTest.
 */
class IpLookupHelperTest extends \PHPUnit_Framework_TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function setUp()
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
    }

    /**
     * @testdox Check if IP outside a request that local IP is returned
     *
     * @covers  \Mautic\CoreBundle\Helper\IpLookupHelper::getIpAddress
     */
    public function testLocalIpIsReturnedWhenNotInRequestScope()
    {
        $ip = $this->getIpHelper()->getIpAddress();

        $this->assertEquals('127.0.0.1', $ip->getIpAddress());
    }

    /**
     * @testdox Check that the first IP is returned when the request is a proxy
     *
     * @covers  \Mautic\CoreBundle\Helper\IpLookupHelper::getIpAddress
     */
    public function testClientIpIsReturnedFromProxy()
    {
        $request = new Request([], [], [], [], [], ['HTTP_X_FORWARDED_FOR' => '73.77.245.52,10.8.0.2,192.168.0.1']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('73.77.245.52', $ip->getIpAddress());
    }

    /**
     * @testdox Check that the first IP is returned with a web proxy
     *
     * @covers  \Mautic\CoreBundle\Helper\IpLookupHelper::getIpAddress
     */
    public function testClientIpIsReturnedFromRequest()
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.53']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('73.77.245.53', $ip->getIpAddress());
    }

    /**
     * @testdox Check that a local IP is returned for internal IPs
     *
     * @covers  \Mautic\CoreBundle\Helper\IpLookupHelper::getIpAddress
     */
    public function testLocalIpIsReturnedForInternalNetworkIp()
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('127.0.0.1', $ip->getIpAddress());
    }

    /**
     * @param null $request
     *
     * @return IpLookupHelper
     */
    private function getIpHelper($request = null)
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $mockRepository = $this
            ->getMockBuilder(IpAddressRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepository->expects($this->any())
            ->method('__call')
            ->with($this->equalTo('findOneByIpAddress'))
            ->willReturn(null);

        $mockEm = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepository));

        $mockCoreParametersHelper = $this
            ->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCoreParametersHelper->expects($this->any())
            ->method('getParameter')
            ->willReturn(null);

        return new IpLookupHelper($requestStack, $mockEm, $mockCoreParametersHelper);
    }
}
