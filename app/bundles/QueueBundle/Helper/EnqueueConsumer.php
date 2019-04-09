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

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\QueueConsumer;

class EnqueueConsumer
{
    /**
     * @var QueueConsumer
     */
    private $consumer;

    /**
     * @var DelegateProcessor
     */
    private $processor;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param QueueConsumer     $consumer
     * @param DelegateProcessor $processor
     * @param QueueMetaRegistry $queueMetaRegistry
     * @param DriverInterface   $driver
     */
    public function __construct(
        QueueConsumer $consumer,
        DelegateProcessor $processor,
        QueueMetaRegistry $queueMetaRegistry,
        DriverInterface $driver
    ) {
        $this->consumer          = $consumer;
        $this->processor         = $processor;
        $this->queueMetaRegistry = $queueMetaRegistry;
        $this->driver            = $driver;
    }

    /**
     * @param int|null $messageLimit
     */
    public function consume($messageLimit = null)
    {
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $queueMeta) {
            $queue = $this->driver->createQueue($queueMeta->getClientName());
            $this->consumer->bind($queue, $this->processor);
        }

        $extensions = [
            new SetupBrokerExtension($this->driver),
        ];

        if (is_numeric($messageLimit)) {
            $extensions[] = new LimitConsumedMessagesExtension($messageLimit);
        }

        $this->consumer->consume(new ChainExtension($extensions));
    }
}
