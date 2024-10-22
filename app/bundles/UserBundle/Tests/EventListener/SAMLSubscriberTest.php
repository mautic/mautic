<?php

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\EventListener\SAMLSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Router;

class SAMLSubscriberTest extends TestCase
{
    /**
     * @var RequestEvent&MockObject
     */
    private MockObject $event;

    /**
     * @var Router&MockObject
     */
    private MockObject $router;

    protected function setUp(): void
    {
        $this->event = $this->createMock(RequestEvent::class);
        $this->event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->router = $this->createMock(Router::class);
    }

    /**
     * Because this subscriber is removed from the kernel if the SAML is disabled,
     * this need to be tested always in the case it's enabled.
     */
    public function testRedirectIsIgnoredIfSamlEnabled(): void
    {
        $redirect   = '/redirect';
        $subscriber = new SAMLSubscriber($this->router);

        $request             = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();

        $request->method('getRequestUri')
            ->willReturn('/saml/login');

        $this->event->method('getRequest')
            ->willReturn($request);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('login')
            ->willReturn($redirect);

        $this->event->expects($this->once())
            ->method('setResponse')
            ->with(new RedirectResponse($redirect));

        $subscriber->onKernelRequest($this->event);
    }
}
