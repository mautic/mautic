<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class EventSchedulerCalculationEvent.
 */
class EventSchedulerCalculationEvent extends Event
{
    /**
     * @var \Mautic\CampaignBundle\Entity\Event
     */
    private $event;
    
    /**
     * @var \DateTime
     */
    private $compareFromDateTime;
    
    /**
     * @var \DateTime
     */
    private $compareToDateTime;

    /**
     * @var Lead
     */
    private $contact;
    
    /**
     * @var \DateTime
     */
    private $executionDateTime;

    /**
     * EventSchedulerCalculationEvent constructor.
     *
     * @param Event    $event
     * @param DateTime $compareFromDateTime
     * @param DateTime $compareToDateTime
     * @param Lead     $contact
     * @param DateTime $executionDateTime
     */
    public function __construct(Mautic\CampaignBundle\Entity\Event $event, \DateTime $compareFromDateTime, \DateTime $compareToDateTime, Lead $contact, \DateTime $executionDateTime)
    {
        $this->event = $event;
        $this->compareFromDateTime = $compareFromDateTime;
        $this->compareToDateTime = $compareToDateTime;
        $this->contact = $contact;
        $this->executionDateTime = $executionDateTime;
    }

    /**
     * Returns the Event entity.
     *
     * @return \Mautic\CampaignBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Returns the compareFromDateTime.
     *
     * @return \DateTime
     */
    public function getCompareFromDateTime()
    {
        return $this->compareFromDateTime;
    }
    
    /**
     * Returns the compareToDateTime.
     *
     * @return \DateTime
     */
    public function getCompareToDateTime()
    {
        return $this->compareToDateTime;
    }
    
    /**
     * Returns the contact (Lead) entity.
     *
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }
    
    /**
     * Returns the executionDateTime.
     *
     * @return \DateTime
     */
    public function getExecutionDateTime()
    {
        return $this->executionDateTime;
    }
    
    /**
     * @param \DateTime $executionDateTime
     *
     * @return $this
     */
    public function setExecutionDateTime($executionDateTime)
    {
        $this->executionDateTime = $executionDateTime;

        return $this;
    }
}
