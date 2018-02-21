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

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

/**
 * Class DateDecorator.
 */
class DateDecorator extends CustomMappedDecorator
{
    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @todo @petr please check this method
     *
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option need to implement this function');
    }
}
