<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Queue;


use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Event\QueueEvent;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class QueueService
 */
class QueueService
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * QueueService constructor.
     * @param CoreParametersHelper $coreParametersHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, EventDispatcherInterface $eventDispatcher)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $queueName
     * @param array $payload
     */
    public function publishToQueue($queueName, array $payload=[])
    {
        $protocol = $this->coreParametersHelper->getParameter('queue_protocol');
        $payload['mauticQueueName'] = $queueName;
        $event = new QueueEvent($protocol, $queueName, 'publish', $payload);
        $this->eventDispatcher->dispatch(QueueEvents::PUBLISH_MESSAGE, $event);
    }

    /**
     * @param string $queueName
     * @param int|null $messages
     */
    public function consumeFromQueue($queueName, $messages=null)
    {
        $protocol = $this->coreParametersHelper->getParameter('queue_protocol');
        $event = new QueueEvent($protocol, $queueName, 'consume', [], $messages);
        $this->eventDispatcher->dispatch(QueueEvents::CONSUME_MESSAGE, $event);
    }

    /**
     * @param string $payload
     * @return QueueConsumerEvent
     */
    public function dispatchConsumerEventFromPayload($payload)
    {
        $payload = unserialize($payload);

        // This is needed since OldSound RabbitMqBundle consumers don't know what their queue is
        $queueName = $payload['mauticQueueName'];
        unset($payload['mauticQueueName']);
        $eventName = "mautic.queue_{$queueName}";

        $event = new QueueConsumerEvent($payload);
        $this->eventDispatcher->dispatch($eventName, $event);
        return $event;
    }

    /**
     * @return bool
     */
    public function isQueueEnabled()
    {
        return $this->coreParametersHelper->getParameter('queue_protocol') != '';
    }
}
