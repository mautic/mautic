<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ApiSubscriber;
use Mautic\CoreBundle\Tests\CommonMocks;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class ApiSubscriberTest extends CommonMocks
{
    public function testIsBasicAuthWithValidBasicAuth()
    {
        $subscriber = new ApiSubscriber(
            $this->getIpLookupHelperMock(),
            $this->getCoreParametersHelperMock(),
            $this->getAuditLogModelMock()
        );

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->headers = new HeaderBag(['Authorization' => 'Basic dXNlcm5hbWU6cGFzc3dvcmQ=']);

        $this->assertTrue($subscriber->isBasicAuth($request));
    }

    public function testIsBasicAuthWithInvalidBasicAuth()
    {
        $subscriber = new ApiSubscriber(
            $this->getIpLookupHelperMock(),
            $this->getCoreParametersHelperMock(),
            $this->getAuditLogModelMock()
        );

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->headers = new HeaderBag(['Authorization' => 'Invalid Basic Auth value']);

        $this->assertFalse($subscriber->isBasicAuth($request));
    }

    public function testIsBasicAuthWithMissingBasicAuth()
    {
        $subscriber = new ApiSubscriber(
            $this->getIpLookupHelperMock(),
            $this->getCoreParametersHelperMock(),
            $this->getAuditLogModelMock()
        );

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->headers = new HeaderBag([]);

        $this->assertFalse($subscriber->isBasicAuth($request));
    }
}
