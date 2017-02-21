<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CustomFormEvent.
 */
class CustomFormEvent extends Event
{
    /**
     * @var
     */
    protected $formName;

    /**
     * @var
     */
    protected $formType;

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @var array
     */
    protected $subscribers = [];

    /**
     * CustomFormEvent constructor.
     *
     * @param $formName
     */
    public function __construct($formName, $formType)
    {
        $this->formName = $formName;
        $this->formType = $formType;
    }

    /**
     * @return mixed
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * @return mixed
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @return array
     */
    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * @param $eventName
     * @param $listener
     */
    public function addListener($eventName, $listener)
    {
        if (!is_callable($listener)) {
            throw new \InvalidArgumentException('$listener must be callable');
        }

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }
}
