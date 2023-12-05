<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Contracts\EventDispatcher\Event;

final class AddColumnBackgroundEvent extends Event
{
    private \Mautic\LeadBundle\Entity\LeadField $leadField;

    public function __construct(LeadField $leadField)
    {
        $this->leadField = $leadField;
    }

    public function getLeadField(): LeadField
    {
        return $this->leadField;
    }
}
