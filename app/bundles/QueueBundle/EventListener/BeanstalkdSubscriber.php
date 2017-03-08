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
use Pheanstalk\Pheanstalk;

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
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * BeanstalkdSubscriber constructor.
     * @param Pheanstalk $pheanstalk
     * @param QueueService $queueService
     */
    public function __construct(Pheanstalk $pheanstalk, QueueService $queueService)
    {
        $this->pheanstalk = $pheanstalk;
        $this->queueService = $queueService;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $this->pheanstalk
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
            $job = $this->pheanstalk
                ->watch($event->getQueueName())
                ->ignore('default')
                ->reserve();

            $this->queueService->dispatchConsumerEventFromPayload($job->getData());

            $messagesConsumed++;
        }
    }
}
