<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class DateDecorator extends CustomMappedDecorator
{
    /**
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): array|string|float|bool|null
    {
        throw new \Exception('Instance of Date option needs to implement this function');
    }
}
