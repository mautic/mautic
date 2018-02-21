<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionAbstract;

abstract class DateMonthAbstract extends DateOptionAbstract
{
    /**
     * @return string
     */
    protected function getModifierForBetweenRange()
    {
        return '+1 month';
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueForBetweenRange()
    {
        return $this->dateTimeHelper->toUtcString('Y-m-%');
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $leadSegmentFilterCrate->getOperator() === '!=' ? 'notLike' : 'like';
    }
}
