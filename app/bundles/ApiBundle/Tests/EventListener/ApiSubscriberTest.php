<?php

namespace Mautic\ApiBundle\Tests\EventListener;

use Mautic\ApiBundle\EventListener\ApiSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApiSubscriberTest extends CommonMocks
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParametersHelper;

    /**
     * @var Translator&MockObject
     */
    private MockObject $translator;

    /**
     * @var Request&MockObject
     */
    private MockObject $request;

    /**
     * @var RequestEvent&MockObject
     */
    private MockObject $event;

    private ApiSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->translator           = $this->createMock(Translator::class);
        $this->request              = $this->createMock(Request::class);
        $this->request->headers     = new HeaderBag();
        $this->event                = $this->createMock(RequestEvent::class);
        $this->subscriber           = new ApiSubscriber(
            $this->coreParametersHelper,
            $this->translator
        );
    }

    public function testOnKernelRequestWhenNotMasterRequest(): void
    {
        $this->event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(false);

        $this->coreParametersHelper->expects($this->never())
            ->method('get');

        $this->subscriber->onKernelRequest($this->event);
    }

    public function testOnKernelRequestOnApiRequestWhenApiDisabled(): void
    {
        $this->event->expects($this->once())
            ->method('isMainRequest')
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
                function (JsonResponse $response): void {
                    $this->assertEquals(403, $response->getStatusCode());
                }
            );

        $this->subscriber->onKernelRequest($this->event);
    }

    public function testOnKernelRequestOnApiRequestWhenApiEnabled(): void
    {
        $this->event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/api/endpoint');
        $matcher = $this->exactly(2);

        $this->coreParametersHelper->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('api_enabled', $parameters[0]);

                    return true;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('api_enable_basic_auth', $parameters[0]);

                    return true;
                }
            });

        $this->subscriber->onKernelRequest($this->event);
    }
}
