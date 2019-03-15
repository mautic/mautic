<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Executioner\Validator;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Action;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Exception\PointTriggerCustomHandledException;

class PointActionTriggerValidator
{
    /**
     * @var PointModel
     */
    private $pointModel;

    /** @var array */
    private $settings;
    /**
     * @var Point
     */
    private $action;

    /** @var array */
    private $completedActionsForContact;

    /**
     * @var Lead
     */
    private $lead;

    private $eventDetails;

    /**
     * PointActionValidator constructor.
     *
     * @param PointModel $pointModel
     * @param            $eventDetails
     * @param            $type
     * @param Lead       $lead
     */
    public function __construct(PointModel $pointModel, $eventDetails, $type, Lead $lead)
    {
        $this->pointModel                 = $pointModel;
        $this->lead                       = $lead;
        $this->completedActionsForContact = $this->pointModel->getRepository()->getCompletedLeadActions($type, $lead->getId());
        $this->eventDetails               = $eventDetails;
    }

    /**
     * @param Point $action
     * @param array $args
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function canChangePoints(Point $action)
    {
        $this->action   = $action;
        $this->settings = $this->getActionSettings();

        // repeatable - always change points
        if ($this->action->getRepeatable()) {
            return true;
        }

        try {
            // validation by callback not passed, stop
            if (!$this->canChangePointsByCallback($this->getCallbackArgs())) {
                return false;
            }
        } catch (PointTriggerCustomHandledException $handledException) {
            // if we want use our custom validation for change points, then stop here and not continue
            return $handledException->canChangePoints();
        }

        // standard validation from logs If we are able to change points for action for contact
        if (!$this->canChangePointsByLog()) {
            return false;
        }

        return true;
    }

    /**
     * Check validation of point action by callback.
     *
     * @param array $args
     *
     * @return bool
     */
    private function canChangePointsByCallback(array $args)
    {
        $callback = (isset($this->settings['callback'])) ? $this->settings['callback'] :
            ['\\Mautic\\PointBundle\\Helper\\EventHelper', 'engagePointAction'];

        if (is_callable($callback)) {
            if (is_array($callback)) {
                $reflection = new \ReflectionMethod($callback[0], $callback[1]);
            } elseif (strpos($callback, '::') !== false) {
                $parts      = explode('::', $callback);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                $reflection = new \ReflectionMethod(null, $callback);
            }

            $pass = [];
            foreach ($reflection->getParameters() as $param) {
                if (isset($args[$param->getName()])) {
                    $pass[] = $args[$param->getName()];
                } else {
                    $pass[] = null;
                }
            }
            if (!$reflection->invokeArgs($this, $pass)) {
                return false;
            }

            return true;
        }
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    private function getActionSettings()
    {
        $availableActions = $this->pointModel->getPointActions();

        if (!isset($availableActions['actions'][$this->action->getType()])) {
            throw new \Exception(sprintf('Action "%s" doesn\'t exist', $this->action->getType()));
        }

        return $availableActions['actions'][$this->action->getType()];
    }

    /**
     * @return array
     */
    private function getCallbackArgs()
    {
        return [
            'action' => [
                'id'         => $this->action->getId(),
                'type'       => $this->action->getType(),
                'name'       => $this->action->getName(),
                'repeatable' => $this->action->getRepeatable(),
                'properties' => $this->action->getProperties(),
                'points'     => $this->action->getDelta(),
            ],
            'lead'         => $this->lead,
            'factory'      => $this->factory, // WHAT?
            'eventDetails' => $this->eventDetails,
        ];
    }

    /**
     * Check If contact already has log of point action.
     *
     * @return bool
     */
    private function canChangePointsByLog()
    {
        return empty($this->completedActionsForContact[$this->action->getId()]);
    }
}
