<?php

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventDailySendLog;
use Mautic\CampaignBundle\Tests\Mock\EventDailySendModelMock;

/**
 * Class EventDailySendModelTest.
 */
class EventDailySendModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDailySendModelMock
     */
    public $eventDailySendModel;

    public function setUp()
    {
        $this->eventDailySendModel = new EventDailySendModelMock();
    }

    public function testCanBeSend()
    {
        $event             = new Event();
        $eventDailySendLog = new EventDailySendLog();

        //no limit
        $this->assertEquals(true, $this->eventDailySendModel->canBeSend($event, $eventDailySendLog));

        //only with limit
        $event->setProperties([
            'daily_max_limit' => 1,
        ]);

        $this->assertEquals(true, $this->eventDailySendModel->canBeSend($event, $eventDailySendLog));

        $eventDailySendLog->setSentCount(5);

        $this->assertEquals(false, $this->eventDailySendModel->canBeSend($event, $eventDailySendLog));
    }

    public function testGetCurrentDayLog()
    {
        $event = new Event();

        $log = $this->eventDailySendModel->getCurrentDayLog($event);

        $this->assertEquals(true, $log instanceof EventDailySendLog);
    }

    public function testIncreaseSentCount()
    {
        $event = new Event();

        $log        = $this->eventDailySendModel->getCurrentDayLog($event);
        $iterations = 6;

        for ($i = 0; $iterations > $i; ++$i) {
            $this->eventDailySendModel->increaseSentCount($log);
        }

        $this->assertEquals(6, $log->getSentCount());
    }
}
