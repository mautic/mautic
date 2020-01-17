<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ApiSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Tests\CommonMocks;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ApiSubscriberTest extends CommonMocks
{
    /**
     * @var IpLookupHelper|PHPUnit_Framework_MockObject_MockObject
     */
    private $ipLookupHelper;

    /**
     * @var CoreParametersHelper|PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParametersHelper;

    /**
     * @var AuditLogModel|PHPUnit_Framework_MockObject_MockObject
     */
    private $auditLogModel;

    /**
     * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var Request|PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var GetResponseEvent|PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var ApiSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->request              = $this->createMock(Request::class);
        $this->event                = $this->createMock(GetResponseEvent::class);
        $this->subscriber           = new ApiSubscriber(
            $this->ipLookupHelper,
            $this->coreParametersHelper,
            $this->auditLogModel,
            $this->translator
        );
    }

    public function testIsBasicAuthWithValidBasicAuth()
    {
        $this->request->headers = new HeaderBag(['Authorization' => 'Basic dXNlcm5hbWU6cGFzc3dvcmQ=']);

        $this->assertTrue($this->subscriber->isBasicAuth($this->request));
    }

    public function testIsBasicAuthWithInvalidBasicAuth()
    {
        $this->request->headers = new HeaderBag(['Authorization' => 'Invalid Basic Auth value']);

        $this->assertFalse($this->subscriber->isBasicAuth($this->request));
    }

    public function testIsBasicAuthWithMissingBasicAuth()
    {
        $this->request->headers = new HeaderBag([]);

        $this->assertFalse($this->subscriber->isBasicAuth($this->request));
    }

    public function testOnKernelRequestWhenNotMasterRequest()
    {
        $this->event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->coreParametersHelper->expects($this->never())
            ->method('getParameter');

        $this->assertNull($this->subscriber->onKernelRequest($this->event));
    }

    public function testOnKernelRequestOnApiRequestWhenApiDisabled()
    {
        $this->event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/api/endpoint');

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('api_enabled')
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->subscriber->onKernelRequest($this->event);
    }

    public function testOnKernelRequestOnApiRequestWhenApiEnabled()
    {
        $this->event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/api/endpoint');

        $this->coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('api_enabled')
            ->willReturn(true);

        $this->subscriber->onKernelRequest($this->event);
    }
}
