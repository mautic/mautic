<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

class DateDecorator extends CustomMappedDecorator
{
    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @throws \Exception
     */
    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option need to implement this function');
    }
}
