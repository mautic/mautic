<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageBuilderEvent.
 */
class PageBuilderEvent extends BuilderEvent
{
    /**
     * @return Page|null
     */
    public function getPage()
    {
        return $this->entity;
    }
}
