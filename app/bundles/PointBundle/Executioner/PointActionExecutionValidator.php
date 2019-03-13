<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Executioner;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Action;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PointActionExecutionValidator
{
    /**
     * @var PointModel
     */
    private $pointModel;

    /** @var object */
    private $eventDetails;

    /** @var array */
    private $settings;
    /**
     * @var Point
     */
    private $action;

    /** @var array */
    private $completedActionsForContact;

    /** @var int/string */
    private $internalId;

    /**
     * PointActionValidator constructor.
     *
     * @param PointModel $pointModel
     */
    public function __construct(PointModel $pointModel, $eventDetails, $type, Lead $lead)
    {
        $this->pointModel                 = $pointModel;
        $this->eventDetails               = $eventDetails;
        $this->completedActionsForContact = $this->pointModel->getRepository()->getCompletedLeadActions($type, $lead->getId());
        $this->propertyAccessor           = new PropertyAccessor();
    }

    /**
     * @param Point $action
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isValid(Point $action)
    {
        $this->action = $action;

        $availableActions = $this->pointModel->getPointActions();

        if (!isset($availableActions['actions'][$this->action->getType()])) {
            throw new \Exception(sprintf('Action "%s" doesn\'t exist', $this->action->getType()));
        }

        $this->settings = $availableActions['actions'][$action->getType()];

        // repeatable - valid always
        if ($this->action->getRepeatable()) {
            return true;
        } elseif ($this->validationByInternalId()) {
            if ($this->hasContactCompletedActionByInternalId()) {
                return false;
            }
        } elseif ($this->hasContactCompletedAction()) {
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
    public function isValidByCallback(array $args)
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
     * Check If contact already has log of point action.
     *
     * @return bool
     */
    private function hasContactCompletedAction()
    {
        return !empty($this->completedActionsForContact[$this->action->getId()]);
    }

    /**
     * Check If contact already has log of point action and internal id.
     *
     * @return bool
     */
    private function hasContactCompletedActionByInternalId()
    {
        if (!$this->internalId = $this->propertyAccessor->getValue($this->eventDetails, $this->settings['internalIdProperty'])) {
            return false;
        }
        if (!array_search($this->internalId, array_column($this->completedActionsForContact, 'internal_id'))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function validationByInternalId()
    {
        return  !empty($this->action->getProperties()['separately']) && $this->hasInternalIdProperty();
    }

    /**
     * @return bool
     */
    private function hasInternalIdProperty()
    {
        return $this->settings['internalIdProperty'] ? true : false;
    }

    /**
     * @return int
     */
    public function getInternalId()
    {
        return $this->internalId;
    }
}
