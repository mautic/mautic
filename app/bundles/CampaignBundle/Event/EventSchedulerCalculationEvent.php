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

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

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
     * @var \DateTimeInterface
     */
    private $compareFromDateTime;

    /**
     * @var \DateTimeInterface
     */
    private $comparedToDateTime;

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var \DateTimeInterface
     */
    private $executionDateTime;

    /**
     * EventSchedulerCalculationEvent constructor.
     *
     * @param Event             $event
     * @param DateTimeInterface $compareFromDateTime
     * @param DateTimeInterface $comparedToDateTime
     * @param Lead|null         $contact
     * @param DateTimeInterface $executionDateTime
     */
    public function __construct(\Mautic\CampaignBundle\Entity\Event $event, \DateTimeInterface $compareFromDateTime, \DateTimeInterface $comparedToDateTime, Lead $contact = null, \DateTimeInterface $executionDateTime)
    {
        $this->event               = $event;
        $this->compareFromDateTime = $compareFromDateTime;
        $this->comparedToDateTime  = $comparedToDateTime;
        $this->contact             = $contact;
        $this->executionDateTime   = $executionDateTime;
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
     * @return \DateTimeInterface
     */
    public function getCompareFromDateTime()
    {
        return $this->compareFromDateTime;
    }

    /**
     * Returns the comparedToDateTime.
     *
     * @return \DateTimeInterface
     */
    public function getCompareToDateTime()
    {
        return $this->comparedToDateTime;
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
     * @return \DateTimeInterface
     */
    public function getExecutionDateTime()
    {
        return $this->executionDateTime;
    }

    /**
     * @param \DateTimeInterface $executionDateTime
     *
     * @return $this
     */
    public function setExecutionDateTime($executionDateTime)
    {
        $this->executionDateTime = $executionDateTime;

        return $this;
    }
}
