<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Field;

class FormFieldEvent extends CommonEvent
{
    /**
     * @param Field $field
     * @param bool  $isNew
     */
    public function __construct(Field &$field, $isNew = false)
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
    public function setField(Field $field)
    {
        $this->entity = $field;
    }
}
