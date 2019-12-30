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
use Mautic\CoreBundle\Tests\CommonMocks;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ApiSubscriberTest extends CommonMocks
{
    /**
     * @var CoreParametersHelper|PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParametersHelper;

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

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->request              = $this->createMock(Request::class);
        $this->request->headers     = new ParameterBag();
        $this->event                = $this->createMock(GetResponseEvent::class);
        $this->subscriber           = new ApiSubscriber(
            $this->coreParametersHelper,
            $this->translator
        );
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

        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(['api_enabled'], ['api_enable_basic_auth'])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->subscriber->onKernelRequest($this->event);
    }
}
