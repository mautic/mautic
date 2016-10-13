<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;

class DynamicContentEvent extends CommonEvent
{
    /**
     * DynamicContentEvent constructor.
     *
     * @param DynamicContent $entity
     * @param bool           $isNew
     */
    public function __construct(DynamicContent $entity, $isNew = false)
    {
        $this->entity = $entity;
        $this->isNew  = $isNew;
    }

    /**
     * @return DynamicContent
     */
    public function getDynamicContent()
    {
        return $this->entity;
    }

    /**
     * @param DynamicContent $entity
     */
    public function setDynamicContent(DynamicContent $entity)
    {
        $this->entity = $entity;
    }
}
