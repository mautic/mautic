<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\LeadTimelineEvent;

class LeadTimelineEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Every event in the timeline should have a unique eventId so test that one is generated if the subscriber forgets
     *
     * @covers \Mautic\LeadBundle\Event\LeadTimelineEvent::addEvent()
     * @covers \Mautic\LeadBundle\Event\LeadTimelineEvent::getEvents()
     * @covers \Mautic\LeadBundle\Event\LeadTimelineEvent::generateEventId()
     */
    public function testEventIdIsGeneratedIfNotSetBySubscriber()
    {
        $payload = [
            [
                'event'      => 'foo',
                'eventLabel' => 'Foo',
                'eventType'  => 'foo',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something',
                ],
                'icon'      => 'fa-tachometer',
                'contactId' => 1,
            ],
            [
                'event'      => 'bar',
                'eventLabel' => 'Bar',
                'eventType'  => 'bar',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something else',
                ],
                'icon'      => 'fa-tachometer',
                'contactId' => 2,
            ],
            [
                'event'      => 'foobar',
                'eventId'    => 'foobar123',
                'eventLabel' => 'Foo Bar',
                'eventType'  => 'foobar',
                'timestamp'  => new \DateTime(),
                'extra'      => [
                    'something' => 'something else',
                ],
                'icon'      => 'fa-tachometer',
                'contactId' => 2,
            ],
        ];

        $event = new LeadTimelineEvent();

        foreach ($payload as $data) {
            $event->addEvent($data);
        }

        $events = $event->getEvents();

        $id1 = hash('crc32', json_encode($payload[0]), false);
        $this->assertTrue(isset($events[0]['eventId']));
        $this->assertEquals('foo'.$id1, $events[0]['eventId']);

        $id2 = hash('crc32', json_encode($payload[1]), false);
        $this->assertTrue(isset($events[1]['eventId']));
        $this->assertEquals('bar'.$id2, $events[1]['eventId']);

        $this->assertTrue(isset($events[2]['eventId']));
        $this->assertEquals('foobar123', $events[2]['eventId']);
    }
}
