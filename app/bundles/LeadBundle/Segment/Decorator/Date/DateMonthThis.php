<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date;

class DateMonthThis extends DateOptionAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate()
    {
        $this->dateTimeHelper->setDateTime('midnight first day of this month', null);
    }

    /**
     * @return string
     */
    protected function getModifierForBetweenRange()
    {
        return '+1 month';
    }
}
