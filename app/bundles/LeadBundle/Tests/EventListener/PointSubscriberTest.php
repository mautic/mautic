<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\PointSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Helper\StageHelper;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PointSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeadModel|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadModel;

    private \Mautic\LeadBundle\EventListener\PointSubscriber $subscriber;
    /**
     * @var StageModel|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $stageModel;

    /**
     * @var Translator|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    protected function setUp(): void
    {
        $this->leadModel   = $this->createMock(LeadModel::class);

        $this->translator  = $this->createMock(Translator::class);
        $logger            = $this->createMock(LoggerInterface::class);
        $this->stageModel  = $this->getMockBuilder(StageModel::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $stageHelper       = new StageHelper($this->leadModel, $this->stageModel, $logger, $this->translator);

        $this->subscriber  = new PointSubscriber($this->leadModel, $stageHelper, $this->translator, $logger);
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

    public function testOnPointTriggerExecutedForChangeStage(): void
    {
        $triggerEvent = new TriggerEvent();
        $contact      = new Lead();
        $triggerEvent->setType('lead.changestage');
        $triggerEvent->setProperties([
            'stage'    => 2,
        ]);

        $this->stageModel->expects($this->once())
            ->method('getEntity')
            ->with(2)
            ->willReturn(new Stage());

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('');

        $this->subscriber->onTriggerExecute(new TriggerExecutedEvent($triggerEvent, $contact));
    }
}
