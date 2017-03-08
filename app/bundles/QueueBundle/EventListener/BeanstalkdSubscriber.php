<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\EventListener;

use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Queue\QueueProtocol;
use Mautic\QueueBundle\Queue\QueueService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BeanstalkdSubscriber
 */
class BeanstalkdSubscriber extends AbstractQueueSubscriber
{
    /**
     * @var string
     */
    protected $protocol = QueueProtocol::BEANSTALKD;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * BeanstalkdSubscriber constructor.
     * @param ContainerInterface $container
     * @param QueueService $queueService
     */
    public function __construct(ContainerInterface $container, QueueService $queueService)
    {
        // The container is needed due to non-required binding of pheanstalk
        $this->container = $container;
        $this->queueService = $queueService;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $this->container->get('leezy.pheanstalk')
            ->useTube($event->getQueueName())
            ->put(serialize($event->getPayload()));
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function consumeMessage(Events\QueueEvent $event)
    {
        $messagesConsumed = 0;

        while ($event->getMessages() === null || $event->getMessages() > $messagesConsumed) {
            $pheanstalk = $this->container->get('leezy.pheanstalk');
            $job = $pheanstalk
                ->watch($event->getQueueName())
                ->ignore('default')
                ->reserve();

            $this->queueService->dispatchConsumerEventFromPayload($job->getData());

            $pheanstalk->delete($job);

            $messagesConsumed++;
        }
    }
}
