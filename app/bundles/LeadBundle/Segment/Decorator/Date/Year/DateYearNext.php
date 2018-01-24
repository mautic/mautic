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

class DateYearNext extends DateYearAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate()
    {
        $this->dateTimeHelper->setDateTime('midnight first day of next year', null);
    }
}
