<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\PointSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use PHPUnit\Framework\MockObject\MockObject;

class PointSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeadModel|MockObject
     */
    private MockObject $leadModel;

    private PointSubscriber $subscriber;
    private MockObject $triggerExecutedEvent;

    private MockObject $triggerEventEntity;

    protected function setUp(): void
    {
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->subscriber           = new PointSubscriber($this->leadModel);
        $this->triggerExecutedEvent = $this->createMock(TriggerExecutedEvent::class);
        $this->triggerEventEntity   = $this->createMock(TriggerEventEntity::class);

        $this->triggerExecutedEvent
            ->method('getTriggerEvent')
            ->willReturn($this->triggerEventEntity);
    }

    public function testOnPointTriggerExecutedIfNotChangeTagsTyoe(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $triggerEvent->setType('unknown.type');

        $this->leadModel->expects($this->never())
            ->method('modifyTags');

        $this->subscriber->onTriggerExecute(new TriggerExecutedEvent($triggerEvent, $contact));
    }

    public function testOnPointTriggerExecutedForChangeTagsTyoe(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $triggerEvent->setType('lead.changetags');
        $triggerEvent->setProperties([
            'add_tags'    => ['tagA'],
            'remove_tags' => null,
        ]);

        $this->leadModel->expects($this->once())
            ->method('modifyTags')
            ->with($contact, ['tagA'], []);

        $this->subscriber->onTriggerExecute(new TriggerExecutedEvent($triggerEvent, $contact));
    }

    public function testThatTheLeadIsAddedToTheSegmentOnTriggerOnLeadSegmentsChangeEvent(): void
    {
        $this->triggerEventEntity
            ->method('getProperties')
            ->willReturn([
                'addToLists'      => 1,
                'removeFromLists' => null,
            ]);

        $this->leadModel->expects($this->once())->method('addToLists');
        $this->subscriber->onLeadSegmentsChange($this->triggerExecutedEvent);
    }

    public function testThatTheLeadIsRemovedFromTheSegmentOnTriggerOnLeadSegmentsChangeEvent(): void
    {
        $this->triggerEventEntity
            ->method('getProperties')
            ->willReturn([
                'removeFromLists' => 1,
                'addToLists'      => null,
            ]);

        $this->leadModel->expects($this->once())->method('removeFromLists');
        $this->subscriber->onLeadSegmentsChange($this->triggerExecutedEvent);
    }

    public function testThatTheObserverForTriggerOnLeadSegmentsChangeEventIsFired(): void
    {
        $subscribers = PointSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(PointEvents::TRIGGER_ON_LEAD_SEGMENTS_CHANGE, $subscribers);
        $this->assertSame(['onLeadSegmentsChange', 0], $subscribers[PointEvents::TRIGGER_ON_LEAD_SEGMENTS_CHANGE]);
    }
}
