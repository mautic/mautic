<?php

namespace Mautic\QueueBundle\EventListener;

use Leezy\PheanstalkBundle\Proxy\PheanstalkProxy;
use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueProtocol;
use Mautic\QueueBundle\Queue\QueueService;
use Pheanstalk\Contract\PheanstalkInterface;
use Pheanstalk\Exception\ServerException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BeanstalkdSubscriber extends AbstractQueueSubscriber
{
    public const DELAY_DURATION = 60;

    /**
     * @var string
     */
    protected $protocol = QueueProtocol::BEANSTALKD;

    /**
     * @var string
     */
    protected $protocolUiTranslation = 'mautic.queue.config.protocol.beanstalkd';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var QueueService
     */
    private $queueService;

    public function __construct(ContainerInterface $container, QueueService $queueService)
    {
        // The container is needed due to non-required binding of pheanstalk
        $this->container    = $container;
        $this->queueService = $queueService;
    }

    public function publishMessage(Events\QueueEvent $event): void
    {
        /* @var PheanstalkProxy $pheanstalk */
        $this->container->get('leezy.pheanstalk')
            ->useTube($event->getQueueName())
            ->put($event->getPayload());
    }

    /**
     * @throws ServerException
     */
    public function consumeMessage(Events\QueueEvent $event): void
    {
        $messagesConsumed = 0;

        while (null === $event->getMessages() || $event->getMessages() > $messagesConsumed) {
            /** @var PheanstalkProxy $pheanstalk */
            $pheanstalk = $this->container->get('leezy.pheanstalk');
            $job        = $pheanstalk
                ->watch($event->getQueueName())
                ->ignore('default')
                ->reserve();

            if (null === $job) {
                continue;
            }

            $consumerEvent = $this->queueService->dispatchConsumerEventFromPayload($job->getData());

            if (QueueConsumerResults::TEMPORARY_REJECT === $consumerEvent->getResult()) {
                $pheanstalk->release($job, PheanstalkInterface::DEFAULT_PRIORITY, static::DELAY_DURATION);
            } elseif (QueueConsumerResults::REJECT === $consumerEvent->getResult()) {
                $pheanstalk->bury($job);
            } else {
                try {
                    $pheanstalk->delete($job);
                } catch (ServerException $e) {
                    if (false === strpos($e->getMessage(), 'Cannot delete job')
                        && false === strpos($e->getMessage(), 'NOT_FOUND')
                    ) {
                        throw $e;
                    }
                }
            }

            ++$messagesConsumed;
        }
    }
}
