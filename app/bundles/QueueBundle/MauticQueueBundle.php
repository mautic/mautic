<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle;

use Leezy\PheanstalkBundle\DependencyInjection\LeezyPheanstalkExtension;
use Mautic\QueueBundle\Queue\QueueProtocol;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticQueueBundle.
 */
class MauticQueueBundle extends Bundle
{
    /**
     * @var array
     */
    private $localParams;

    public function __construct(array $localParams)
    {
        $this->localParams = $localParams;
    }

    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $extension       = $this->createContainerExtension();
            $this->extension = $extension;
        }

        return $this->extension ?: null;
    }

    public function createContainerExtension()
    {
        if (empty($this->localParams['queue_protocol'])) {
            return null;
        }

        $queueProtocol = $this->localParams['queue_protocol'];

        if (QueueProtocol::RABBITMQ === $queueProtocol) {
            return new OldSoundRabbitMqExtension();
        }

        if (QueueProtocol::BEANSTALKD === $queueProtocol) {
            return new LeezyPheanstalkExtension();
        }
    }
}
