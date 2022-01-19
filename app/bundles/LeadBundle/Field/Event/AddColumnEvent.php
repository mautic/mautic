<?php

declare(strict_types=1);

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

final class AddColumnEvent extends Event
{
    /**
     * @var LeadField
     */
    private $leadField;

    /**
     * @var bool
     */
    private $shouldProcessInBackground;

    public function __construct(LeadField $leadField, bool $shouldProcessInBackground)
    {
        $this->leadField                 = $leadField;
        $this->shouldProcessInBackground = $shouldProcessInBackground;
    }

    public function getLeadField(): LeadField
    {
        return $this->leadField;
    }

    public function shouldProcessInBackground(): bool
    {
        return $this->shouldProcessInBackground;
    }
}
