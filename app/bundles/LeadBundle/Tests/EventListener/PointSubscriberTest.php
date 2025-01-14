<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\PointSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use PHPUnit\Framework\MockObject\MockObject;

class PointSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeadModel|MockObject
     */
    private $leadModel;

    /**
     * @var PointSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        $this->leadModel  = $this->createMock(LeadModel::class);
        $this->subscriber = new PointSubscriber($this->leadModel);
    }

    public function testOnPointTriggerExecutedIfNotChangeTagsTyoe()
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $triggerEvent->setType('unknown.type');

        $this->leadModel->expects($this->never())
            ->method('modifyTags');

        $this->subscriber->onTriggerExecute(new TriggerExecutedEvent($triggerEvent, $contact));
    }

    public function testOnPointTriggerExecutedForChangeTagsTyoe()
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
}
