<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\EventSchedulerCalculationEvent;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var EventLogger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventLogger;

    /**
     * @var Interval|\PHPUnit_Framework_MockObject_MockObject
     */
    private $intervalScheduler;

    /**
     * @var DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeScheduler;

    /**
     * @var EventCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventCollector;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var CoreParametersHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $coreParamtersHelper;

    protected function setUp()
    {
        $this->logger              = new NullLogger();
        $this->eventLogger         = $this->createMock(EventLogger::class);
        $this->intervalScheduler   = $this->createMock(Interval::class);
        $this->dateTimeScheduler   = $this->createMock(DateTime::class);
        $this->eventCollector      = $this->createMock(EventCollector::class);
        $this->dispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->coreParamtersHelper = $this->createMock(CoreParametersHelper::class);
    }
    
    public function testGetExecutionDateTime()
    {
        // Set up test vars.
        $event = new Event();
        $compareFromDateTime = new \DateTime('2018-07-03 09:20:45');
        $comparedToDateTime = new \DateTime('2018-07-03 09:20:30');
        $contact = $this->getMockBuilder(Lead::class)->getMock();
        $expected = new \DateTime('2018-07-03 09:20:45');
        
        // Spy on the event dispatcher to ensure that the expected event is dispatched.
        $this->dispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(CampaignEvents::EVENT_SCHEDULER_POST_CALCULATE_EXECUTION_DATE_TIME, $this->isInstanceOf(EventSchedulerCalculationEvent::class));
        
        // Call the test method.
        $executionDateTime = $this->getScheduler()->getExecutionDateTime(
                                    $event,
                                    $compareFromDateTime,
                                    $comparedToDateTime,
                                    $contact
                                );
        
        // Assert that the expected response is returned.
        $this->assertEquals($expected, $executionDateTime);
    }

    public function testShouldScheduleIgnoresSeconds()
    {
        $this->assertFalse(
            $this->getScheduler()->shouldSchedule(
                new \DateTime('2018-07-03 09:20:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    public function testShouldSchedule()
    {
        $this->assertTrue(
            $this->getScheduler()->shouldSchedule(
                new \DateTime('2018-07-03 09:21:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    private function getScheduler()
    {
        return new EventScheduler(
            $this->logger,
            $this->eventLogger,
            $this->intervalScheduler,
            $this->dateTimeScheduler,
            $this->eventCollector,
            $this->dispatcher,
            $this->coreParamtersHelper
        );
    }
}
