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
use Mautic\LeadBundle\Entity\Tag;

/**
 * Class TagEvent.
 */
class TagEvent extends CommonEvent
{
    /**
     * @param Tag  $tag
     * @param bool $isNew
     */
    public function __construct(Tag $tag, $isNew = false)
    {
        $this->entity = $tag;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Tag entity.
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->entity;
    }
}
