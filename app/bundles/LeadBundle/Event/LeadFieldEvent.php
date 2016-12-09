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
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class LeadFieldEvent.
 */
class LeadFieldEvent extends CommonEvent
{
    /**
     * @param LeadField $field
     * @param bool      $isNew
     */
    public function __construct(LeadField &$field, $isNew = false)
    {
        $this->entity = &$field;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Field entity.
     *
     * @return Field
     */
    public function getField()
    {
        return $this->entity;
    }

    /**
     * Sets the Field entity.
     *
     * @param Field $field
     */
    public function setField(LeadField $field)
    {
        $this->entity = $field;
    }
}
