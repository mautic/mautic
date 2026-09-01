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
        $this->assertNotInstanceOf(\DateTimeInterface::class, $event->getTriggerHour());
    }

    public function testSetTriggerHourWhenArray(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour(['date' => self::DATE]);
        $this->assertEquals(new \DateTime(self::DATE), $event->getTriggerHour());
    }

    public function testSetTriggerHourWhenArrayWithTimezone(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour([
            'date'     => '2026-12-15 19:00:00.000000',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->assertSame('19:00', $event->getTriggerHour()->format('H:i'));
        $this->assertSame('Europe/Berlin', $event->getTriggerHour()->getTimezone()->getName());
    }

    public function testSetTriggerDateWhenArrayWithTimezoneKeepsInstant(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerDate([
            'date'     => '2026-12-15 18:00:00.000000',
            'timezone' => 'UTC',
        ]);

        $triggerDate = $event->getTriggerDate();
        $this->assertInstanceOf(\DateTime::class, $triggerDate);
        $this->assertSame('UTC', $triggerDate->getTimezone()->getName());
        $this->assertSame('2026-12-15 18:00:00', $triggerDate->format('Y-m-d H:i:s'));

        $asBerlin = (clone $triggerDate)->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $this->assertSame('2026-12-15 19:00:00', $asBerlin->format('Y-m-d H:i:s'));
    }

    public function testSetTriggerHourWhenString(): void
    {
        $event = new Event();
        $event->setName(self::TEST_NAME);
        $event->setTriggerHour(self::DATE);
        $this->assertEquals(new \DateTime(self::DATE), $event->getTriggerHour());
    }
}
