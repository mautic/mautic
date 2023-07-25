<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

interface ContactDecoratorForeignInterface
{
    /**
     * Returns the name of a foreign contact column used in JOIN condition (usually contact_id or lead_id).
     */
    public function getForeignContactColumn(ContactSegmentFilterCrate $contactSegmentFilterCrate): string;
}
