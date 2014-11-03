<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class TriggerBuilderEvent
 *
 * @package Mautic\PointBundle\Event
 */
class TriggerBuilderEvent extends Event
{

    /**
     * @var array
     */
    private $actions = array();

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds an action to the list of available .
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.action
     * @param array $action - can contain the following keys:
     *  'group'       => (required) translation string to group events by
     *  'label'       => (required) what to display in the list
     *  'description' => (optional) short description of event
     *  'template'    => (optional) template to use for the action's HTML in the point builder
     *      i.e AcmeMyBundle:PointAction:theaction.html.php
     *  'formType'    => (optional) name of the form type SERVICE for the action
     *  'callback'    => (required) callback function that will be passed when the action is triggered
     *      The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *          Mautic\CoreBundle\Factory\MauticFactory $factory
     *          Mautic\LeadBundle\Entity\Lead $lead
     *          array $event = array(
     *              'id' => int
     *              'type' => string
     *              'name' => string
     *              'properties' => array()
     *              'trigger' => array(
     *                  'id'     => int
     *                  'name'   => string,
     *                  'points' => int
     *                  'color'  => string
     *              )
     *         )
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function addEvent($key, array $action)
    {
        if (array_key_exists($key, $this->events)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another action. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyComponent(
            array('group', 'label', 'callback'),
            array('callback'),
            $action
        );

        //translate the group
        $action['group'] = $this->translator->trans($action['group']);
        $this->events[$key] = $action;
    }

    /**
     * Get events
     *
     * @return array
     */
    public function getEvents()
    {
        uasort($this->events, function ($a, $b) {
            return strnatcasecmp(
                $a['group'], $b['group']);
        });
        return $this->events;
    }

    /**
     * @param array $keys
     * @param array $methods
     * @param array $component
     *
     * @return void
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
                throw new InvalidArgumentException($component[$m] . ' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }
}
