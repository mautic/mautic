<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class DateDecorator extends CustomMappedDecorator
{
    /**
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): mixed
    {
        throw new \Exception('Instance of Date option needs to implement this function');
    }
}
