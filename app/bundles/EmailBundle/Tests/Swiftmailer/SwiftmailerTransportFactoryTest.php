<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer;

use Mautic\EmailBundle\Swiftmailer\SwiftmailerTransportFactory;
use Mautic\EmailBundle\Swiftmailer\Transport\SparkpostTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;

class SwiftmailerTransportFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * @var RequestContext|MockObject
     */
    private $requestContext;

    /**
     * @var \Swift_Events_EventDispatcher|MockObject
     */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->requestContext  = $this->createMock(RequestContext::class);
        $this->eventDispatcher = $this->createMock(\Swift_Events_EventDispatcher::class);
    }

    public function testServiceIsFoundAndReturned()
    {
        $transportMock = $this->createMock(SparkpostTransport::class);

        $transport = 'mautic.transport.sparkpost';
        $this->container->expects($this->once())
            ->method('has')
            ->with($transport)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with($transport)
            ->willReturn($transportMock);

        $options = ['transport' => $transport];

        $foundTransport = SwiftmailerTransportFactory::createTransport($options, $this->requestContext, $this->eventDispatcher, $this->container);

        $this->assertInstanceOf(SparkpostTransport::class, $foundTransport);
    }

    public function testExceptionIsThrownIfServiceNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);

        $transport = 'mautic.transport.sparkpost';
        $this->container->expects($this->once())
            ->method('has')
            ->with($transport)
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('get');

        $options = ['transport' => $transport];

        SwiftmailerTransportFactory::createTransport($options, $this->requestContext, $this->eventDispatcher, $this->container);
    }

    public function testSmtpTransportIsReturnedIfServiceNotUsed()
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('smtp')
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('get');

        $options = ['transport' => 'smtp'];

        $transport = SwiftmailerTransportFactory::createTransport($options, $this->requestContext, $this->eventDispatcher, $this->container);

        $this->assertInstanceOf(\Swift_Transport_EsmtpTransport::class, $transport);
    }
}
