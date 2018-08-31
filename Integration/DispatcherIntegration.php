<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trait DispatcherIntegration -
 *
 * this integration is really useless, it should be provided as service not an integration
 * it solely to demostrate the approach
 *
 * @package Mautic\IntegrationsBundle\Integration
 */
trait DispatcherIntegration
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param       $eventName
     * @param array $keys
     *
     * @return array
     */
    protected function dispatchIntegrationKeyEvent($eventName, $keys = [])
    : array
    {
        /** @var PluginIntegrationKeyEvent $event */
        $event = $this->dispatcher->dispatch(
            $eventName,
            new PluginIntegrationKeyEvent($this, $keys)
        );

        return $event->getKeys();
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    : EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     *
     * @return BasicIntegration
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    : BasicIntegration
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}
