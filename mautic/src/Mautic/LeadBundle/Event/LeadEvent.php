<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class LeadEvent
 *
 * @package Mautic\LeadBundle\Event
 */
class LeadEvent extends CommonEvent
{
    /**
     * @param Lead $lead
     * @param bool $isNew
     */
    public function __construct(Lead &$lead, $isNew = false)
    {
        $this->entity  =& $lead;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Lead entity
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Sets the Lead entity
     *
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->entity = $lead;
    }

    /**
     * Determines changes to original entity
     *
     * @return mixed
     */
    public function getChanges()
    {
        $changeset = parent::getChanges();

        //Check for and add updated custom field values
        $updatedFields = $this->entity->getUpdatedFields();
        if (!empty($updatedFields)) {
            if (!$this->isNew) {
                $changeset['fields'] = $updatedFields;
            } else {
                $fields = array();
                //we don't need the old since it'll just be blank for new leads
                foreach ($updatedFields as $k => $v) {
                    if (!empty($v[1])) {
                        $fields[$k] = $v[1];
                    }
                }
                //actually entity so add it for serialization
                $changeset->fieldChangeset = $fields;
            }
        }
        return $changeset;
    }
}