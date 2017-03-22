<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class LeadChangeEvent.
 */
class LeadChangeEvent extends Event
{
    /**
     * @var Lead
     */
    private $oldLead;

    /**
     * @var
     */
    private $oldTrackingId;

    /**
     * @var Lead
     */
    private $newLead;

    /**
     * @var
     */
    private $newTrackingId;

    /**
     * @param Lead $oldLead
     * @param      $oldTrackingId
     * @param Lead $newLead
     * @param      $newTrackingId
     */
    public function __construct(Lead $oldLead, $oldTrackingId, Lead $newLead, $newTrackingId)
    {
        $this->oldLead       = $oldLead;
        $this->oldTrackingId = $oldTrackingId;
        $this->newLead       = $newLead;
        $this->newTrackingId = $newTrackingId;
    }

    /**
     * @return Lead
     */
    public function getOldLead()
    {
        return $this->oldLead;
    }

    /**
     * @return mixed
     */
    public function getOldTrackingId()
    {
        return $this->oldTrackingId;
    }

    /**
     * @return Lead
     */
    public function getNewLead()
    {
        return $this->newLead;
    }

    /**
     * @return mixed
     */
    public function getNewTrackingId()
    {
        return $this->newTrackingId;
    }
}
