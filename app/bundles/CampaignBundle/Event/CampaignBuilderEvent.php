<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignBuilderEvent
 *
 * @package Mautic\CampaignBundle\Event
 */
class CampaignBuilderEvent extends Event
{
    private $triggers = array();
    private $actions = array();
    private $translator;

    public function __construct ($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Add an event to the list of available .
     *
     * @param string $key     - a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $trigger - can contain the following keys:
     *                        'group'       => (required) translation string to group triggers by
     *                        'label'       => (required) what to display in the list
     *                        'description' => (optional) short description of event
     *                        'template'    => (optional) template to use for the action's HTML in the campaign builder
     *                            i.e AcmeMyBundle:CampaignEvent:theaction.html.php
     *                        'formType'    => (optional) name of the form type SERVICE for the action
     *                        'callback'    => (optional) callback function that will be passed when the event is triggered
     *                            The callback function should return a bool to determine if the trigger's actions
     *                            should be executed.  For example, only trigger actions for specific entities.
     *                            it can can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                              mixed $passthrough Whatever the bundle passes when triggering the event
     *                              Mautic\CoreBundle\Factory\MauticFactory $factory
     *                              Mautic\LeadBundle\Entity\Lead $lead
     *                              array $event
     */
    public function addTrigger ($key, array $trigger)
    {
        if (array_key_exists($key, $this->triggers)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another trigger. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('group', 'label'),
            array('callback'),
            $trigger
        );

        //translate the group
        $trigger['group'] = $this->translator->trans($trigger['group']);
        $this->triggers[$key] = $trigger;
    }

    /**
     * Get triggers
     *
     * @return array
     */
    public function getTriggers ()
    {
        $triggers = $this->triggers;
        uasort($triggers, function ($a, $b) {
            return strnatcasecmp(
                $a['group'], $b['group']);
        });

        return $triggers;
    }


    /**
     * Add an action to the list of available .
     *
     * @param string $key    - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array  $action - can contain the following keys:
     *                       'group'       => (required) translation string to group triggers by
     *                       'label'       => (required) what to display in the list
     *                       'description' => (optional) short description of event
     *                       'template'    => (optional) template to use for the action's HTML in the campaign builder
     *                           i.e AcmeMyBundle:CampaignTrigger:theaction.html.php
     *                       'formType'    => (optional) name of the form type SERVICE for the action
     *                       'callback'    => (required) callback function that will be passed when the action is triggered
     *                            The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *                              Mautic\CoreBundle\Factory\MauticFactory $factory
     *                              Mautic\LeadBundle\Entity\Lead $lead
     *                              array $event
     */
    public function addAction ($key, array $action)
    {
        if (array_key_exists($key, $this->triggers)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('group', 'label', 'callback'),
            array('callback'),
            $action
        );

        //translate the group
        $action['group']     = $this->translator->trans($action['group']);
        $this->actions[$key] = $action;
    }

    /**
     * Get triggers
     *
     * @return array
     */
    public function getActions ()
    {
        $actions = $this->actions;
        uasort($actions, function ($a, $b) {
            return strnatcasecmp(
                $a['group'], $b['group']);
        });

        return $actions;
    }

    /**
     * @param array $component
     */
    private function verifyComponent (array $keys, array $methods, array $component)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $component)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($component[$m]) && !is_callable($component[$m], true)) {
                throw new InvalidArgumentException($component[$m] . ' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}