<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Event\InternalObjectRouteEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RouteHelperTest extends TestCase
{
    /**
     * @var ObjectProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectProvider;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var RouteHelper
     */
    private $routeHelper;

    protected function setUp(): void
    {
        $this->objectProvider = $this->createMock(ObjectProvider::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->routeHelper    = new RouteHelper($this->objectProvider, $this->dispatcher);
    }

    public function testContactRoute(): void
    {
        $internalObject = new Contact();
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE,
                $this->callback(function (InternalObjectRouteEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(1, $event->getId());

                    // Mock a subscriber.
                    $event->setRoute('route/for/id/1');

                    return true;
                })
            );

        $this->routeHelper->getRoute(Contact::NAME, 1);
    }

    public function testCompanyRoute(): void
    {
        $internalObject = new Company();
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Company::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE,
                $this->callback(function (InternalObjectRouteEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(1, $event->getId());

                    // Mock a subscriber.
                    $event->setRoute('route/for/id/1');

                    return true;
                })
            );

        $this->routeHelper->getRoute(Company::NAME, 1);
    }

    public function testExceptionThrownWithUnsupportedObject(): void
    {
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with('FooBar')
            ->willThrowException(new ObjectNotFoundException('FooBar object not found'));

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->expectException(ObjectNotSupportedException::class);

        $this->routeHelper->getRoute('FooBar', 1);
    }

    public function testLink(): void
    {
        $internalObject = new Contact();
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE,
                $this->callback(function (InternalObjectRouteEvent $event) use ($internalObject) {
                    $this->assertSame($internalObject, $event->getObject());
                    $this->assertSame(1, $event->getId());

                    // Mock a subscriber.
                    $event->setRoute('route/for/id/1');

                    return true;
                })
            );

        $link = $this->routeHelper->getLink(Contact::NAME, 1, 'Hello');
        $this->assertEquals('<a href="route/for/id/1">Hello</a>', $link);
    }

    public function testLinkCsv(): void
    {
        $internalObject = new Contact();
        $this->objectProvider->expects($this->exactly(2))
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn($internalObject);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE,
                    $this->callback(function (InternalObjectRouteEvent $event) use ($internalObject) {
                        $this->assertSame($internalObject, $event->getObject());
                        $this->assertSame(1, $event->getId());

                        // Mock a subscriber.
                        $event->setRoute('route/for/id/1');

                        return true;
                    }),
                ],
                [
                    IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE,
                    $this->callback(function (InternalObjectRouteEvent $event) use ($internalObject) {
                        $this->assertSame($internalObject, $event->getObject());
                        $this->assertSame(2, $event->getId());

                        // Mock a subscriber.
                        $event->setRoute('route/for/id/2');

                        return true;
                    }),
                ]
            );

        $csv = $this->routeHelper->getLinkCsv(Contact::NAME, [1, 2]);
        $this->assertEquals('[<a href="route/for/id/1">1</a>], [<a href="route/for/id/2">2</a>]', $csv);
    }
}
