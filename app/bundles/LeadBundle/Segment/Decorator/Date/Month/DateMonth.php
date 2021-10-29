<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class DateMonth extends DateMonthAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime('midnight first day of this month', null);
    }

    /**
     * @return string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $date           = $this->dateOptionParameters->getDefaultDate();
        $filter         =  $contactSegmentFilterCrate->getFilter();
        $relativeFilter =  trim(str_replace('month', '', $filter));

        if ($relativeFilter) {
            $date->modify($relativeFilter);
        }

        return $date->toLocalString('%-m-%');
    }
}
