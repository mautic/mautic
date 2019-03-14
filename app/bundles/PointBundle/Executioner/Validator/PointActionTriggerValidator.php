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
     * PointActionValidator constructor.
     *
     * @param PointModel $pointModel
     */
    public function __construct(PointModel $pointModel, $type, Lead $lead)
    {
        $this->pointModel                 = $pointModel;
        $this->completedActionsForContact = $this->pointModel->getRepository()->getCompletedLeadActions($type, $lead->getId());
    }

    /**
     * @param Point $action
     *
     * @param array $args
     *
     * @return bool
     * @throws \Exception
     */
    public function canTrigger(Point $action, array $args)
    {
        $this->action = $action;
        $this->settings = $this->getActionSettings();

        // repeatable - valid always
        if ($this->action->getRepeatable()) {
            return true;
        }
        // validation by callback
        if ($this->canTriggerByCallback($args)) {
            return true;
        }

        // point log already exits
        if ($this->canTriggerByLog()) {
            return true;
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
    private function canTriggerByCallback(array $args)
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
     * Check If contact already has log of point action.
     *
     * @return bool
     */
    private function canTriggerByLog()
    {
        return (empty($this->action->getProperties()['execute_each']) && empty($this->completedActionsForContact[$this->action->getId()]));
    }
}
