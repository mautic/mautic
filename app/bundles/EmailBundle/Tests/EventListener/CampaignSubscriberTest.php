<?php

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventDailySendLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Tests\Mock\EventDailySendModelMock;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Tests\Mock\EventModelMock;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class CampaignSubscriberTest.
 */
class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    public $emailModel;

    /**
     * @var EventModelMock
     */
    public $eventModel;

    /**
     * @var EventDailySendModelMock
     */
    public $eventDailySendModel;

    /**
     * @var CampaignExecutionEvent
     */
    public $campaignExecutionEvent;

    /**
     * @var int
     */
    public $sendEmailCount = 0;

    public function setUp()
    {
        $this->emailModel             = $this->getMockEmailModel();
        $this->eventModel             = $this->createMockClass(EventModelMock::class);
        $this->eventDailySendModel    = $this->createMockClass(EventDailySendModelMock::class);
        $this->campaignExecutionEvent = $this->getCampaignExecutionEvent();
        $this->sendEmailCount         = 0;
    }

    public function testOnCampaignTriggerWithoutLimitsAction()
    {
        $event = new Event();
        $event->setProperties([
            'daily_max_limit' => 0,
        ]);

        $this->eventModel->setEntity($event);

        $campaignSubscriber = $this->getMockCampaignSubscriber();
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);

        $this->assertEquals(true, (bool) $this->campaignExecutionEvent->getResult());
        $this->assertEquals(3, $this->emailModel->getSendEmailCount());
    }

    public function testOnCampaignTriggerWithLimitsAction()
    {
        $event = new Event();
        $event->setProperties([
            'daily_max_limit' => 24,
        ]);

        $this->eventModel->setEntity($event);

        $campaignSubscriber = $this->getMockCampaignSubscriber();
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);

        $this->assertEquals(true, (bool) $this->campaignExecutionEvent->getResult());
        $this->assertEquals(5, $this->emailModel->getSendEmailCount());
    }

    public function testOnCampaignTriggerQueuedAction()
    {
        $date = new \DateTime();

        $log = new EventDailySendLog();
        $log->increaseSentCount();
        $log->setDate($date);

        $event = new Event();
        $event->setProperties(['daily_max_limit' => 1]);
        $event->addDailySendLog(1, $log);

        $this->eventModel->setEntity($event);
        $this->eventDailySendModel->setDate($date);

        $campaignSubscriber = $this->getMockCampaignSubscriber();

        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
        $campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);

        $this->assertEquals(
            [
                'queued' => 1,
            ],
            $this->campaignExecutionEvent->getResult()
        );
        $this->assertEquals(0, $this->emailModel->getSendEmailCount());
    }

    /**
     * @return CampaignExecutionEvent
     */
    private function getCampaignExecutionEvent()
    {
        return new CampaignExecutionEvent([
            'lead' => [
                'email' => 'sender@test.pl',
            ],
            'event' => [
                'id'         => 1,
                'properties' => [
                    'email' => 1,
                ],
            ],
            'eventDetails'    => [],
            'systemTriggered' => [],
            'eventSettings'   => [],
        ], []);
    }

    /**
     * @return CampaignSubscriber
     */
    private function getMockCampaignSubscriber()
    {
        return $this->getMockBuilder(CampaignSubscriber::class)
            ->setConstructorArgs([
                $this->createMockClass(LeadModel::class),
                $this->emailModel,
                $this->eventModel,
                $this->createMockClass(MessageQueueModel::class),
                $this->eventDailySendModel,
            ])
            ->setMethods(null)
            ->getMock()
            ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockEmailModel()
    {
        $mock = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntity', 'sendEmail', 'getSendEmailCount'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue(new Email()));

        $mock->expects($this->any())
            ->method('sendEmail')
            ->willReturnCallback(function () {
                ++$this->sendEmailCount;

                return true;
            });

        $mock->expects($this->any())
            ->method('getSendEmailCount')
            ->willReturnCallback(function () {
                return $this->sendEmailCount;
            });

        return $mock;
    }

    /**
     * @param $classPath
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockClass($classPath)
    {
        return $this->getMockBuilder($classPath)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
    }
}
