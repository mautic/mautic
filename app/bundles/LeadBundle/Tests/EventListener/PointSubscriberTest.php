<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\PointSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class PointSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&LeadModel
     */
    private MockObject $leadModel;

    /**
     * @var MockObject&StageModel
     */
    private MockObject $stageModel;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    private PointSubscriber $subscriber;

    /**
     * @var MockObject&TriggerExecutedEvent
     */
    private MockObject $triggerExecutedEvent;

    /**
     * @var MockObject&TriggerEventEntity
     */
    private MockObject $triggerEventEntity;

    protected function setUp(): void
    {
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->stageModel           = $this->createMock(StageModel::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->subscriber           = new PointSubscriber($this->leadModel, $this->stageModel, $this->translator, $this->createStub(LoggerInterface::class));
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
            ->willReturn(true)
            ->with($contact, ['tagA'], []);

        $this->subscriber->onTriggerExecute(new TriggerExecutedEvent($triggerEvent, $contact));
    }

    public function testOnPointTriggerExecutedForChangeStage(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $stage        = new Stage();
        $stage->setIsPublished(true);
        $triggerEvent->setType('lead.changestage');
        $triggerEvent->setProperties([
            'addstage' => 2,
        ]);

        $this->stageModel->expects($this->once())
            ->method('getEntity')
            ->with(2)
            ->willReturn($stage);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.lead.point.trigger')
            ->willReturn('Contact triggers');

        $this->leadModel->expects($this->once())
            ->method('changeStage')
            ->with($contact, $stage, 'Contact triggers');

        $event = new TriggerExecutedEvent($triggerEvent, $contact);
        $this->subscriber->onTriggerExecute($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnPointTriggerExecutedForRemoveStage(): void
    {
        $triggerEvent = new TriggerEvent();
        $stage        = new Stage();
        $contact      = new Lead();
        $contact->setStage($stage);
        $triggerEvent->setType('lead.changestage');
        $triggerEvent->setProperties([
            'addstage' => 0,
        ]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.stage.event.removed.batch')
            ->willReturn('Manually Removed');

        $this->leadModel->expects($this->once())
            ->method('removeFromStage')
            ->with($contact, $stage, 'Manually Removed');

        $event = new TriggerExecutedEvent($triggerEvent, $contact);
        $this->subscriber->onTriggerExecute($event);

        $this->assertTrue($event->getResult());
    }

    public function testOnPointTriggerExecutedForMissingStageMarksEventFailed(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $triggerEvent->setType('lead.changestage');
        $triggerEvent->setProperties([
            'addstage' => 404,
        ]);

        $this->stageModel->expects($this->once())
            ->method('getEntity')
            ->with(404)
            ->willReturn(null);

        $this->leadModel->expects($this->never())
            ->method('changeStage');

        $event = new TriggerExecutedEvent($triggerEvent, $contact);
        $this->subscriber->onTriggerExecute($event);

        $this->assertFalse($event->getResult());
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
