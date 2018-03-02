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

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

/**
 * Class DateDecorator.
 */
class DateDecorator extends CustomMappedDecorator
{
    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @TODO @petr please check this method
     *
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option need to implement this function');
    }

    public function getDefaultDate()
    {
        return new DateTimeHelper('midnight today', null, 'local');
    }
}
