<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;

trigger_deprecation('mautic/core', '4.3', 'The "%s" class is deprecated, will be removed in 5.0', CustomFormEvent::class);

/**
 * @deprecated since M4, will be removed in M5 because it's not used
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
     * @param string $formName
     * @param string $formType
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

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }
}
