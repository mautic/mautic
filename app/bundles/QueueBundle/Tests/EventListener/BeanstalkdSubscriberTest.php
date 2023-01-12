<?php

namespace Mautic\QueueBundle\Tests\EventListener;

use Leezy\PheanstalkBundle\Proxy\PheanstalkProxy;
use Mautic\QueueBundle\Event\QueueEvent;
use Mautic\QueueBundle\EventListener\BeanstalkdSubscriber;
use Mautic\QueueBundle\Queue\QueueService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BeanstalkdSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PheanstalkProxy|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pheanstalkProxy;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    private $container;

    /**
     * @var QueueService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queueService;

    /**
     * @var BeanstalkdSubscriber
     */
    private $beanstalkdSubscriber;

    public function setUp(): void
    {
        parent::setUp();
        $this->pheanstalkProxy      = $this->createMock(PheanstalkProxy::class);
        $this->container            = $this->createMock(ContainerInterface::class);
        $this->queueService         = $this->createMock(QueueService::class);
        $this->beanstalkdSubscriber = new BeanstalkdSubscriber($this->container, $this->queueService);
    }

    public function testPublishMessage()
    {
        $queueName = 'queueName';
        $event     = new QueueEvent('', $queueName);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('leezy.pheanstalk')
            ->willReturn($this->pheanstalkProxy);

        $this->pheanstalkProxy
            ->expects($this->once())
            ->method('useTube')
            ->with($queueName)
            ->willReturn($this->pheanstalkProxy);

        $this->pheanstalkProxy
            ->expects($this->once())
            ->method('put')
            ->with('[]');

        $this->beanstalkdSubscriber->publishMessage($event);
    }
}
