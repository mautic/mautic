<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignDecisionTriggerEvent
 *
 * @package Mautic\CampaignBundle\Event
 */
class CampaignDecisionTriggerEvent extends Event
{
    protected $lead;
    protected $events;
    protected $entities;
    protected $decisionType;
    protected $decisionEventDetails;

    /**
     * @param $lead
     * @param $decisionType
     * @param $decisionEventDetails
     * @param $events
     * @param $entities
     */
    public function __construct($lead, $decisionType, $decisionEventDetails, $events, $entities)
    {
        $this->lead                 = $lead;
        $this->decisionType         = $decisionType;
        $this->decisionEventDetails = $decisionEventDetails;
        $this->events               = $events;
        $this->entities             = $entities;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return mixed
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @return mixed
     */
    public function getDecisionType()
    {
        return $this->decisionType;
    }

    /**
     * @return mixed
     */
    public function getDecisionEventDetails()
    {
        return $this->decisionEventDetails;
    }
}