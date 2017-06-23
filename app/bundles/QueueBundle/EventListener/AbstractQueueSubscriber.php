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

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\QueueEvents;

abstract class AbstractQueueSubscriber extends CommonSubscriber
{
    protected $protocol              = '';
    protected $protocolUiTranslation = '';

    abstract public function publishMessage(Events\QueueEvent $event);

    abstract public function consumeMessage(Events\QueueEvent $event);

    abstract public function buildConfig(Events\QueueConfigEvent $event);

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            QueueEvents::PUBLISH_MESSAGE => ['onPublishMessage', 0],
            QueueEvents::CONSUME_MESSAGE => ['onConsumeMessage', 0],
            QueueEvents::BUILD_CONFIG    => ['onBuildConfig', 0],
        ];
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function onPublishMessage(Events\QueueEvent $event)
    {
        if (!$event->checkContext($this->protocol)) {
            return;
        }

        $this->publishMessage($event);
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function onConsumeMessage(Events\QueueEvent $event)
    {
        if (!$event->checkContext($this->protocol)) {
            return;
        }

        $this->consumeMessage($event);
    }

    public function onBuildConfig(Events\QueueConfigEvent $event)
    {
        $event->addProtocolChoice($this->protocol, $this->protocolUiTranslation);
        $this->buildConfig($event);
    }
}
