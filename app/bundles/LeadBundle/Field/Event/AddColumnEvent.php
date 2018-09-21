<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\EventDispatcher\Event;

class AddColumnEvent extends Event
{
    /**
     * @var LeadField
     */
    private $entity;

    /**
     * @var bool
     */
    private $shouldProcessInBackground;

    /**
     * @param bool $shouldProcessInBackground
     */
    public function __construct(LeadField $entity, $shouldProcessInBackground)
    {
        $this->entity                    = $entity;
        $this->shouldProcessInBackground = $shouldProcessInBackground;
    }

    /**
     * @return LeadField
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return bool
     */
    public function shouldProcessInBackground()
    {
        return $this->shouldProcessInBackground;
    }
}
