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

use Mautic\CampaignBundle\EventCollector\EventCollector;
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
