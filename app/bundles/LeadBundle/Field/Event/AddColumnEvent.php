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
    private $leadField;

    /**
     * @var bool
     */
    private $shouldProcessInBackground;

    /**
     * @param bool $shouldProcessInBackground
     */
    public function __construct(LeadField $leadField, $shouldProcessInBackground)
    {
        $this->leadField                 = $leadField;
        $this->shouldProcessInBackground = $shouldProcessInBackground;
    }

    /**
     * @return LeadField
     */
    public function getLeadField()
    {
        return $this->leadField;
    }

    /**
     * @return bool
     */
    public function shouldProcessInBackground()
    {
        return $this->shouldProcessInBackground;
    }
}
