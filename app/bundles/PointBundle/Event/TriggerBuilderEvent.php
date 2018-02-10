<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TriggerBuilderEvent.
 */
class TriggerBuilderEvent extends Event
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds an action to the list of available .
     *
     * @param string $key   - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $event - can contain the following keys:
     *                      'label'           => (required) what to display in the list
     *                      'description'     => (optional) short description of event
     *                      'template'        => (optional) template to use for the action's HTML in the point builder
     *                      i.e AcmeMyBundle:PointAction:theaction.html.php
     *                      'formType'        => (optional) name of the form type SERVICE for the action
     *                      'formTypeOptions' => (optional) array of options to pass to formType
     *                      'callback'        => (required) callback function that will be passed when the action is triggered
     *                      The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                      Mautic\CoreBundle\Factory\MauticFactory $factory
     *                      Mautic\PointBundle\Entity\TriggerEvent  $event
     *                      Mautic\LeadBundle\Entity\Lead           $lead
     *
     * @throws InvalidArgumentException
     */
    public function addEvent($key, array $event)
    {
        if (array_key_exists($key, $this->events)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['group', 'label'],
            ['callback'],
            $event
        );

        //Support for old way with callback and new event based system
        //Could be removed after all events will be refactored to events. The key 'eventName' will be mandatory and 'callback' will be removed.
        if (!array_key_exists('callback', $event) && !array_key_exists('eventName', $event)) {
            throw new InvalidArgumentException("One of the 'callback' or 'eventName' has to be provided. Use 'eventName' for new code");
        }

        $event['label']       = $this->translator->trans($event['label']);
        $event['group']       = $this->translator->trans($event['group']);
        $event['description'] = (isset($event['description'])) ? $this->translator->trans($event['description']) : '';

        $this->events[$key] = $event;
    }

    /**
     * Get events.
     *
     * @return array
     */
    public function getEvents()
    {
        uasort($this->events, function ($a, $b) {
            return strnatcasecmp(
                $a['label'], $b['label']);
        });

        return $this->events;
    }

    /**
     * @param array $keys
     * @param array $methods
     * @param array $component
     *
     * @throws InvalidArgumentException
     */
    private function verifyComponent(array $keys, array $methods, array $component)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $component)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($component[$m]) && !is_callable($component[$m], true)) {
                throw new InvalidArgumentException($component[$m].' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}
