<?php

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ApiSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Tests\CommonMocks;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Translation\TranslatorInterface;

class ApiSubscriberTest extends CommonMocks
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var GetResponseEvent|MockObject
     */
    private $event;

    /**
     * @var ApiSubscriber
     */
    private $subscriber;

    protected function setUp(): void
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
            ->method('get');

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
            ->method('get')
            ->with('api_enabled')
            ->willReturn(false);

        $this->event->expects($this->once())
            ->method('setResponse')
            ->with($this->isInstanceOf(JsonResponse::class))
            ->willReturnCallback(
                function (JsonResponse $response) {
                    $this->assertEquals(403, $response->getStatusCode());
                }
            );

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
            ->method('get')
            ->withConsecutive(['api_enabled'], ['api_enable_basic_auth'])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->subscriber->onKernelRequest($this->event);
    }
}
