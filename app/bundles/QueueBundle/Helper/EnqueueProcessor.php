<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Helper;

use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueService;

class EnqueueProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const TOPIC = 'mautic';

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * EnqueueProcessor constructor.
     *
     * @param QueueService $queueService
     */
    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $event = $this->queueService->dispatchConsumerEventFromPayload($message->getBody());

        switch ($event->getResult()) {
            case QueueConsumerResults::TEMPORARY_REJECT:
            case QueueConsumerResults::DO_NOT_ACKNOWLEDGE:
                return self::REQUEUE;
            case QueueConsumerResults::ACKNOWLEDGE:
                return self::ACK;
            case QueueConsumerResults::REJECT:
                return self::REJECT;
            default:
                throw new \LogicException(sprintf('Unsupported result: "%s"', $event->getResult()));
        }
    }

    public static function getSubscribedTopics()
    {
        return [self::TOPIC];
    }
}
