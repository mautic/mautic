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
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CustomFormEvent.
 */
class CustomFormEvent extends Event
{
    /**
     * @var string
     */
    protected $formName;

    /**
     * @var string
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
     * @var FormBuilderInterface
     */
    private $formBuilder;

    /**
     * CustomFormEvent constructor.
     *
     * @param string               $formName
     * @param string               $formType
     * @param FormBuilderInterface $formBuilder
     */
    public function __construct($formName, $formType, FormBuilderInterface $formBuilder)
    {
        $this->formName    = $formName;
        $this->formType    = $formType;
        $this->formBuilder = $formBuilder;
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->formName;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
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
