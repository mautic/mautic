<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\EventDispatcher\Event;

class PointChangeActionExecutedEvent extends Event
{
    /**
     * @var Point
     */
    private $pointAction;

    /**
     * @var Lead
     */
    private $lead;

    private $eventDetails;

    /**
     * @var bool
     */
    private $result;

    /**
     * @var array
     */
    private $completedActions;

    /**
     * PointChangeActionExecutedEvent constructor.
     *
     * @param       $eventDetails
     * @param array $completedActions
     */
    public function __construct(Point $pointAction, Lead $lead, $eventDetails, $completedActions = [])
    {
        $this->pointAction      = $pointAction;
        $this->lead             = $lead;
        $this->eventDetails     = $eventDetails;
        $this->completedActions = $completedActions;
    }

    /**
     * @return bool
     */
    public function canChangePoints()
    {
        return $this->result;
    }

    public function setSucceded()
    {
        $this->result = true;
    }

    public function setFailed()
    {
        $this->result = false;
    }

    /**
     * @return bool
     */
    public function setStatusFromLogs()
    {
        $this->result = !(isset($this->completedActions[$this->pointAction->getId()]));
    }

    /**
     * @param $internalId
     *
     * @return bool
     */
    public function setStatusFromLogsForInternalId($internalId)
    {
        $this->result = !(in_array($internalId, array_column($this->completedActions, 'internal_id')));
    }

    /**
     * @return Point
     */
    public function getPointAction()
    {
        return $this->pointAction;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return array
     */
    public function getCompletedActions()
    {
        return $this->completedActions;
    }
}
