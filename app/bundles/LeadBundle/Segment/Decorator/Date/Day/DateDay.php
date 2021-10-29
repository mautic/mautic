<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class DateDay extends DateDayAbstract
{
    /**
     * @return string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $date           = $this->dateOptionParameters->getDefaultDate();
        $filter         =  $contactSegmentFilterCrate->getFilter();
        $relativeFilter =  trim(str_replace('day', '', $filter));

        if ($relativeFilter) {
            $date->modify($relativeFilter);
        }

        return $date->toLocalString('%-%-d%');
    }
}
