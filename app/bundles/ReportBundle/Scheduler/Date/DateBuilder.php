<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Date;

use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Mautic\ReportBundle\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;

class DateBuilder
{
    /**
     * @var SchedulerBuilder
     */
    private $schedulerBuilder;

    /**
     * @var DateHelper
     */
    private $dateHelper;

    public function __construct(SchedulerBuilder $schedulerBuilder, DateHelper $dateHelper)
    {
        $this->schedulerBuilder = $schedulerBuilder;
        $this->dateHelper       = $dateHelper;
    }

    public function getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency)
    {
        $entity = new SchedulerEntity($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);

        try {
            $recurrences = $this->schedulerBuilder->getNextEvents($entity, 10);
        } catch (InvalidSchedulerException $e) {
            return [];
        } catch (NotSupportedScheduleTypeException $e) {
            return [];
        }

        $dates = [];
        foreach ($recurrences as $recurrence) {
            $dates[] = $this->dateHelper->toShort($recurrence->getStart());
        }

        return $dates;
    }
}
