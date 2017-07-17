<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CoreBundle\Event\ComponentValidationTrait;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class CampaignBuilderEvent.
 */
class CampaignBuilderEvent extends Event
{
    use ComponentValidationTrait;

    /**
     * @var array
     */
    private $decisions = [];

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * Holds info if some property has been already sorted or not.
     *
     * @var array
     */
    private $sortCache = [];

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     */
    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Add an lead decision to the list of available .
     *
     * @param string $key      a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $decision can contain the following keys:
     *                         $decision = [
     *                         'label'                   => (required) what to display in the list
     *                         'eventName'               => (required) The event name to fire when this event is triggered.
     *                         'description'             => (optional) short description of event
     *                         'formType'                => (optional) name of the form type SERVICE for the action
     *                         'formTypeOptions'         => (optional) array of options to pass to the formType service
     *                         'formTheme'               => (optional) form theme
     *                         'connectionRestrictions'  => (optional) Array of events to restrict this event to. Implicit events
     *                         [
     *                         'anchor' => [], // array of anchors this event should _not_ be allowed to connect to in the format of eventType.anchorName, e.g. decision.no
     *                         'source' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to connect into this event
     *                         'target' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to flow from this event
     *                         ]
     *                         ]
     */
    public function addDecision($key, array $decision)
    {
        if (array_key_exists($key, $this->decisions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another contact action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['label', ['eventName', 'callback']],
            $decision,
            ['callback']
        );

        $decision['label']       = $this->translator->trans($decision['label']);
        $decision['description'] = (isset($decision['description'])) ? $this->translator->trans($decision['description']) : '';

        $this->decisions[$key] = $decision;
    }

    /**
     * Get decisions.
     *
     * @return mixed
     */
    public function getDecisions()
    {
        return $this->sort('decisions');
    }

    /**
     * @deprecated - use addDecision instead
     *
     * @param       $key
     * @param array $decision
     */
    public function addLeadDecision($key, array $decision)
    {
        $this->addDecision($key, $decision);
    }

    /**
     * @deprecated - use getDecisions instead
     *
     * @return array
     */
    public function getLeadDecisions()
    {
        return $this->getDecisions();
    }

    /**
     * Add an lead condition to the list of available conditions.
     *
     * @param string $key   a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $event can contain the following keys:
     *                      $condition = [
     *                      'label'                   => (required) what to display in the list
     *                      'eventName'               => (required) The event name to fire when this event is triggered.
     *                      'description'             => (optional) short description of event
     *                      'formType'                => (optional) name of the form type SERVICE for the action
     *                      'formTypeOptions'         => (optional) array of options to pass to the formType service
     *                      'formTheme'               => (optional) form theme
     *                      'connectionRestrictions'  => (optional) Array of events to restrict this event to. Implicit events
     *                      [
     *                      'anchor' => [], // array of anchors this event should _not_ be allowed to connect to in the format of eventType.anchorName, e.g. decision.no
     *                      'source' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to connect into this event
     *                      'target' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to flow from this event
     *                      ]
     *                      ]
     */
    public function addCondition($key, array $event)
    {
        if (array_key_exists($key, $this->conditions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another contact action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['label', ['eventName', 'callback']],
            $event,
            ['callback']
        );

        $event['label']       = $this->translator->trans($event['label']);
        $event['description'] = (isset($event['description'])) ? $this->translator->trans($event['description']) : '';

        $this->conditions[$key] = $event;
    }

    /**
     * Get lead conditions.
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->sort('conditions');
    }

    /**
     * @deprecated use addCondition instead
     *
     * @param       $key
     * @param array $event
     */
    public function addLeadCondition($key, array $event)
    {
        $this->addCondition($key, $event);
    }

    /**
     * @deprecated use getConditions() instead
     *
     * @return array
     */
    public function getLeadConditions()
    {
        return $this->getConditions();
    }

    /**
     * Add an action to the list of available .
     *
     * @param string $key    a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $action can contain the following keys:
     *                       $action = [
     *                       'label'               => (required) what to display in the list
     *                       'eventName'           => (required) The event to fire when this event is triggered.
     *                       'description'         => (optional) short description of event
     *                       'formType'            => (optional) name of the form type SERVICE for the action
     *                       'formTypeOptions'     => (optional) array of options to pass to the formType service
     *                       'formTheme'           => (optional) form theme
     *                       'timelineTemplate'    => (optional) custom template for the lead timeline
     *                       'connectionRestrictions'  => (optional) Array of events to restrict this event to. Implicit events
     *                       [
     *                       'anchor' => [], // array of anchors this event should _not_ be allowed to connect to in the format of eventType.anchorName, e.g. decision.no
     *                       'source' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to connect into this event
     *                       'target' => ['action' => [], 'decision' => [], 'condition' => []], // array of event keys allowed to flow from this event
     *                       ]
     *                       ]
     */
    public function addAction($key, array $action)
    {
        if (array_key_exists($key, $this->actions)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            ['label', ['eventName', 'callback']],
            $action,
            ['callback']
        );

        //translate the group
        $action['label']       = $this->translator->trans($action['label']);
        $action['description'] = (isset($action['description'])) ? $this->translator->trans($action['description']) : '';

        $this->actions[$key] = $action;
    }

    /**
     * Get actions.
     *
     * @return array
     */
    public function getActions()
    {
        return $this->sort('actions');
    }

    /**
     * Sort internal actions, decisions and conditions arrays.
     *
     * @param string $property name
     *
     * @return array
     */
    protected function sort($property)
    {
        if (empty($this->sortCache[$property])) {
            uasort(
                $this->{$property},
                function ($a, $b) {
                    return strnatcasecmp(
                        $a['label'],
                        $b['label']
                    );
                }
            );
            $this->sortCache[$property] = true;
        }

        return $this->{$property};
    }
}
