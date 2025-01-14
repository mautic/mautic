<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\EnvironmentSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EnvironmentSubscriberTest extends TestCase
{
    private EnvironmentSubscriber $environmentSubscriber;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelperMock;

    protected function setUp(): void
    {
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->environmentSubscriber    = new EnvironmentSubscriber($this->coreParametersHelperMock);
    }

    public function testGetSubscribedEvents(): void
    {
        Assert::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['onKernelRequestSetTimezone', 128],
                    ['onKernelRequestSetLocale', 101],
                ],
            ],
            $this->environmentSubscriber::getSubscribedEvents()
        );
    }

    public function testSetLocaleThatDoesNotHavePreviousSession(): void
    {
        $requestEventMock = $this->createMock(RequestEvent::class);
        $requestMock      = $this->createMock(Request::class);
        $requestEventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock->expects($this->once())
            ->method('hasPreviousSession')
            ->willReturn(false);

        $this->environmentSubscriber->onKernelRequestSetLocale($requestEventMock);
    }

    public function testSetLocaleWithUserLanguagePreference(): void
    {
        $requestEventMock     = $this->createMock(RequestEvent::class);
        $requestMock          = $this->createMock(Request::class);
        $sessionInterfaceMock = $this->createMock(SessionInterface::class);
        $requestEventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock->expects($this->once())
            ->method('hasPreviousSession')
            ->willReturn(true);
        $requestMock->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn($sessionInterfaceMock);
        $sessionInterfaceMock->expects($this->once())
            ->method('get')
            ->with('_locale')
            ->willReturn('en_US');
        $requestMock->expects($this->once())
            ->method('setLocale')
            ->with('en_US');
        $sessionInterfaceMock->expects($this->once())
            ->method('set')
            ->with('_locale')
            ->willReturn('en_US');
        $this->coreParametersHelperMock->expects($this->never())
            ->method('get')
            ->with('locale');

        $this->environmentSubscriber->onKernelRequestSetLocale($requestEventMock);
    }

    public function testSetLocaleWithSystemLanguage(): void
    {
        $requestEventMock     = $this->createMock(RequestEvent::class);
        $requestMock          = $this->createMock(Request::class);
        $sessionInterfaceMock = $this->createMock(SessionInterface::class);
        $requestEventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock->expects($this->once())
            ->method('hasPreviousSession')
            ->willReturn(true);
        $requestMock->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn($sessionInterfaceMock);
        $sessionInterfaceMock->expects($this->once())
            ->method('get')
            ->with('_locale')
            ->willReturn(null);
        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('locale')
            ->willReturn('en_GB');
        $requestMock->expects($this->once())
            ->method('setLocale')
            ->with('en_GB');
        $sessionInterfaceMock->expects($this->once())
            ->method('set')
            ->with('_locale')
            ->willReturn('en_GB');

        $this->environmentSubscriber->onKernelRequestSetLocale($requestEventMock);
    }
}
