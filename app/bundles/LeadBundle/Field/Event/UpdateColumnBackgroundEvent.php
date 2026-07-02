<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Event;

use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Contracts\EventDispatcher\Event;

final class UpdateColumnBackgroundEvent extends Event
{
    public function __construct(private readonly LeadField $leadField)
    {
    }

    public function getLeadField(): LeadField
    {
        return $this->leadField;
    }
}
