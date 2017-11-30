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

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;

/**
 * Class LeadNoteEvent.
 */
class LeadNoteEvent extends CommonEvent
{
    /**
     * @param LeadNote $note
     * @param bool     $isNew
     */
    public function __construct(LeadNote $note, $isNew = false)
    {
        $this->entity = $note;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the LeadNote entity.
     *
     * @return LeadNote
     */
    public function getNote()
    {
        return $this->entity;
    }

    /**
     * Sets the LeadNote entity.
     *
     * @param LeadNote $note
     */
    public function setLeadNote(LeadNote $note)
    {
        $this->entity = $note;
    }

    /**
     * Returns the Lead.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity->getLead();
    }
}
