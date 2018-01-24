<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionAbstract;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

abstract class DateYearAbstract extends DateOptionAbstract
{
    /**
     * @return string
     */
    protected function getModifierForBetweenRange()
    {
        return '+1 year';
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueForBetweenRange()
    {
        return $this->dateTimeHelper->toUtcString('Y-%');
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperatorForBetweenRange(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $leadSegmentFilterCrate->getOperator() === '!=' ? 'notLike' : 'like';
    }
}
