<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Validator;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Validator\ScheduleIsValidValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ScheduleIsValidValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testNoSchedule()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);

        $report = $this->getMockBuilder(Report::class)
            ->disableOriginalConstructor()
            ->getMock();

        $report->expects($this->once())
            ->method('isScheduled')
            ->with()
            ->willReturn(false);

        $report->expects($this->once())
            ->method('setAsNotScheduled')
            ->with();

        $schedulerBuilderMock->expects($this->never())
            ->method('getNextEvent');

        $scheduleIsValidValidator->validate($report, $constraintMock);
    }

    public function testNoEmailProvided()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintViolationBuilderInterfaceMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock->expects($this->once())
            ->method('buildViolation')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('atPath')
            ->with('toAddress')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('addViolation')
            ->with();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);
        $scheduleIsValidValidator->initialize($executionContextInterfaceMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress(null);

        $schedulerBuilderMock->expects($this->never())
            ->method('getNextEvent');

        $scheduleIsValidValidator->validate($report, $constraintMock);
    }

    public function testValidDailySchedule()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_DAILY);
        $report->setScheduleDay('day');
        $report->setScheduleMonthFrequency('frequency');

        $schedulerBuilderMock->expects($this->once())
            ->method('getNextEvent')
            ->with($report);

        $scheduleIsValidValidator->validate($report, $constraintMock);

        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame('DAILY', $report->getScheduleUnit());
        $this->assertNull($report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testValidWeeklySchedule()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_WEEKLY);
        $report->setScheduleDay(SchedulerEnum::DAY_MO);
        $report->setScheduleMonthFrequency('frequency');

        $schedulerBuilderMock->expects($this->once())
            ->method('getNextEvent')
            ->with($report);

        $scheduleIsValidValidator->validate($report, $constraintMock);

        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame('WEEKLY', $report->getScheduleUnit());
        $this->assertSame('MO', $report->getScheduleDay());
        $this->assertNull($report->getScheduleMonthFrequency());
    }

    public function testValidMonthlySchedule()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_MONTHLY);
        $report->setScheduleDay(SchedulerEnum::DAY_MO);
        $report->setScheduleMonthFrequency(SchedulerEnum::MONTH_FREQUENCY_FIRST);

        $schedulerBuilderMock->expects($this->once())
            ->method('getNextEvent')
            ->with($report);

        $scheduleIsValidValidator->validate($report, $constraintMock);

        $this->assertTrue($report->isScheduled());
        $this->assertSame('xxx', $report->getToAddress());
        $this->assertSame('MONTHLY', $report->getScheduleUnit());
        $this->assertSame('MO', $report->getScheduleDay());
        $this->assertSame('1', $report->getScheduleMonthFrequency());
    }

    public function testInvalidScheduler()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintViolationBuilderInterfaceMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.report.schedule.notValid')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('atPath')
            ->with('isScheduled')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('addViolation')
            ->with();

        $schedulerBuilderMock->expects($this->never())
            ->method('getNextEvent');

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);
        $scheduleIsValidValidator->initialize($executionContextInterfaceMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_MONTHLY);
        $report->setScheduleDay(SchedulerEnum::DAY_MO);
        $report->setScheduleMonthFrequency('Invalid frequency');

        $scheduleIsValidValidator->validate($report, $constraintMock);
    }

    public function testInvalidEvent()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintViolationBuilderInterfaceMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.report.schedule.notValid')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('atPath')
            ->with('isScheduled')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('addViolation')
            ->with();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);
        $scheduleIsValidValidator->initialize($executionContextInterfaceMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_MONTHLY);
        $report->setScheduleDay(SchedulerEnum::DAY_MO);
        $report->setScheduleMonthFrequency(SchedulerEnum::MONTH_FREQUENCY_FIRST);

        $schedulerBuilderMock->expects($this->once())
            ->method('getNextEvent')
            ->with($report)
            ->willThrowException(new InvalidSchedulerException());

        $scheduleIsValidValidator->validate($report, $constraintMock);
    }

    public function testNotSupportedScheduleType()
    {
        $schedulerBuilderMock = $this->getMockBuilder(SchedulerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintMock = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $constraintViolationBuilderInterfaceMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $executionContextInterfaceMock->expects($this->once())
            ->method('buildViolation')
            ->with('mautic.report.schedule.notSupportedType')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('atPath')
            ->with('isScheduled')
            ->willReturn($constraintViolationBuilderInterfaceMock);

        $constraintViolationBuilderInterfaceMock->expects($this->once())
            ->method('addViolation')
            ->with();

        $scheduleIsValidValidator = new ScheduleIsValidValidator($schedulerBuilderMock);
        $scheduleIsValidValidator->initialize($executionContextInterfaceMock);

        $report = new Report();
        $report->setIsScheduled(true);
        $report->setToAddress('xxx');
        $report->setScheduleUnit(SchedulerEnum::UNIT_MONTHLY);
        $report->setScheduleDay(SchedulerEnum::DAY_MO);
        $report->setScheduleMonthFrequency(SchedulerEnum::MONTH_FREQUENCY_FIRST);

        $schedulerBuilderMock->expects($this->once())
            ->method('getNextEvent')
            ->with($report)
            ->willThrowException(new NotSupportedScheduleTypeException());

        $scheduleIsValidValidator->validate($report, $constraintMock);
    }
}
