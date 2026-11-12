<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(LeadTimelineEvent::class)]
final class LeadTimelineEventTest extends \PHPUnit\Framework\TestCase
{
    #[TestDox('Every event in the timeline should have a unique eventId so test that one is generated if the subscriber forgets')]
    public function testEventIdIsGeneratedIfNotSetBySubscriber(): void
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
                'icon'      => 'ri-speed-up-line',
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
                'icon'      => 'ri-speed-up-line',
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
                'icon'      => 'ri-speed-up-line',
                'contactId' => 2,
            ],
        ];

        $event = new LeadTimelineEvent();

        foreach ($payload as $data) {
            $event->addEvent($data);
        }

        $events = $event->getEvents();

        $id1 = hash('crc32', json_encode($payload[0]), false);
        $this->assertArrayHasKey('eventId', $events[0]);
        $this->assertEquals('foo'.$id1, $events[0]['eventId']);

        $id2 = hash('crc32', json_encode($payload[1]), false);
        $this->assertArrayHasKey('eventId', $events[1]);
        $this->assertEquals('bar'.$id2, $events[1]['eventId']);

        $this->assertArrayHasKey('eventId', $events[2]);
        $this->assertEquals('foobar123', $events[2]['eventId']);
    }

    public function testLeadIdsAreAddedToQueryOptions(): void
    {
        $event = new LeadTimelineEvent(null, ['search' => '', 'leadIds' => ['3', 1, 3]]);

        $this->assertSame([3, 1], $event->getLeadIds());
        $this->assertSame([3, 1], $event->getQueryOptions()['leadIds']);
    }

    public function testEmptyLeadIdsMatchNoContact(): void
    {
        $event = new LeadTimelineEvent(null, ['search' => '', 'leadIds' => []]);

        $this->assertSame([0], $event->getQueryOptions()['leadIds']);
    }

    public function testLeadIdsAreIgnoredWithoutFilterOrWithLead(): void
    {
        $withoutFilter = new LeadTimelineEvent(null, ['search' => '']);
        $this->assertArrayNotHasKey('leadIds', $withoutFilter->getQueryOptions());

        $lead = new Lead();
        $lead->setId(5);
        $withLead = new LeadTimelineEvent($lead, ['search' => '', 'leadIds' => [1, 2]]);
        $this->assertSame([], $withLead->getLeadIds());
        $this->assertArrayNotHasKey('leadIds', $withLead->getQueryOptions());
        $this->assertSame(5, $withLead->getQueryOptions()['leadId']);
    }

    public function testEventsOfOtherContactsAreDroppedWithLeadIds(): void
    {
        $event = new LeadTimelineEvent(null, ['search' => '', 'includeEvents' => [], 'excludeEvents' => [], 'leadIds' => [1]]);

        foreach ([1, 2, null] as $contactId) {
            $event->addEvent([
                'event'      => 'foo',
                'eventId'    => 'foo'.$contactId,
                'eventLabel' => 'Foo',
                'eventType'  => 'foo',
                'timestamp'  => new \DateTime(),
                'contactId'  => $contactId,
            ]);
        }

        $this->assertSame([1], array_column($event->getEvents(), 'contactId'));
    }
}
