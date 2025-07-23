<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    private const TEST_NAME = 'Test Name';
    private const DATE      = '2021-10-08 08:00:00';

    public function testSetTriggerHourWhenEmpty(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour('');
        $this->assertNull($event->getTriggerHour());
    }

    public function testSetTriggerHourWhenArray(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour(['date' => self::DATE]);
        $this->assertEquals(new \DateTime(self::DATE), $event->getTriggerHour());
    }

    public function testSetTriggerHourWhenString(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour(self::DATE);
        $this->assertEquals(new \DateTime(self::DATE), $event->getTriggerHour());
    }
}
