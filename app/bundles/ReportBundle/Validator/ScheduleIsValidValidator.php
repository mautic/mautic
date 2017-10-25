<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\ReportBundle\Validator;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Exception\ScheduleNotValidException;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ScheduleIsValidValidator extends ConstraintValidator
{
    /** @var SchedulerBuilder */
    private $schedulerBuilder;

    public function __construct(SchedulerBuilder $schedulerBuilder)
    {
        $this->schedulerBuilder = $schedulerBuilder;
    }

    /**
     * @param Report     $report
     * @param Constraint $constraint
     */
    public function validate($report, Constraint $constraint)
    {
        if (!$report->isScheduled()) {
            $report->setAsNotScheduled();

            return;
        }
        if ($report->isScheduledDaily()) {
            $report->ensureIsDailyScheduled();
            $this->buildScheduler($report);

            return;
        }
        if ($report->isScheduledWeekly()) {
            try {
                $report->ensureIsWeeklyScheduled();
                $this->buildScheduler($report);

                return;
            } catch (ScheduleNotValidException $e) {
                $this->addViolation();
            }
        }
        if ($report->isScheduledMonthly()) {
            try {
                $report->ensureIsMonthlyScheduled();
                $this->buildScheduler($report);

                return;
            } catch (ScheduleNotValidException $e) {
                $this->addViolation();
            }
        }
    }

    private function addViolation()
    {
        $this->context->buildViolation('mautic.report.scheduler.notValid')
            ->atPath('isScheduled')
            ->addViolation();
    }

    private function buildScheduler(Report $report)
    {
        try {
            $this->schedulerBuilder->getNextEvent($report);

            return;
        } catch (InvalidSchedulerException $e) {
            $message = 'mautic.report.scheduler.invalidScheduler';
        } catch (NotSupportedScheduleTypeException $e) {
            $message = 'mautic.report.scheduler.notSupportedType';
        }

        $this->context->buildViolation($message)
            ->atPath('isScheduled')
            ->addViolation();
    }
}
