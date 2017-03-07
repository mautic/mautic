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
use Mautic\QueueBundle\Event\QueueEvent;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * QueueService constructor.
     * @param CoreParametersHelper $coreParametersHelper
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, EventDispatcher $eventDispatcher)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $queueName
     * @param array $payload
     */
    public function publishToQueue($queueName, $payload=[])
    {
        $protocol = $this->coreParametersHelper->getParameter('queue_protocol');
        $event = new QueueEvent($protocol, $queueName, 'publish', $payload);
        $this->eventDispatcher->dispatch(QueueEvents::PUBLISH_MESSAGE, $event);
    }

    /**
     * @param string $queueName
     * @param integer|null $messages
     */
    public function consumeFromQueue($queueName, $messages=null)
    {
        $protocol = $this->coreParametersHelper->getParameter('queue_protocol');
        $event = new QueueEvent($protocol, $queueName, 'consume', [], $messages);
        $this->eventDispatcher->dispatch(QueueEvents::CONSUME_MESSAGE, $event);

    }

    /**
     * @return bool
     */
    public function isQueueEnabled()
    {
        return $this->coreParametersHelper->getParameter('queue_protocol') != '';
    }
}
