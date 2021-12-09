<?php

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\EventListener\CampaignSendSubscriber;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignSendSubscriberTest extends TestCase
{
    /**
     * @var mixed[]
     */
    private $args;

    /**
     * @var MockObject|SmsModel
     */
    private MockObject $smsModel;

    /**
     * @var MockObject|TransportChain
     */
    private MockObject $transportChain;

    protected function setUp(): void
    {
        $this->smsModel       = $this->createMock(SmsModel::class);
        $this->transportChain = $this->createMock(TransportChain::class);

        $lead = new Lead();
        $lead->setId(1);
        $this->args = [
            'lead'            => $lead,
            'event'           => [
                'type'       => 'sms.send_text_sms',
                'properties' => ['sms' => 1],
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];
    }

    public function testSendDeletedSms(): void
    {
        $this->smsModel->expects(self::once())->method('getEntity')->willReturn(null);

        $event = new CampaignExecutionEvent($this->args, false, null);

        $this->CampaignSendSubscriber()->onCampaignTriggerAction($event);
        self::assertTrue((bool) $event->getResult()['failed']);
        self::assertSame('mautic.sms.campaign.failed.missing_entity', $event->getResult()['reason']);
    }

    public function testSendUnpublishedSms(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $sms = new Sms();
        $sms->setIsPublished(false);
        $this->smsModel->expects(self::once())->method('getEntity')->willReturn($sms);

        $event = new CampaignExecutionEvent($this->args, false, null);

        $this->CampaignSendSubscriber()->onCampaignTriggerAction($event);
        self::assertTrue((bool) $event->getResult()['failed']);
        self::assertSame('mautic.sms.campaign.failed.unpublished', $event->getResult()['reason']);
    }

    private function CampaignSendSubscriber(): CampaignSendSubscriber
    {
        return new CampaignSendSubscriber($this->smsModel, $this->transportChain);
    }

    public function testOnCampaignTriggerBatchAction(): void
    {
        $sms = $this->createMock(Sms::class);
        $sms->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        // Partial mock, mocks just getRepository
        $smsModel = $this->getMockBuilder(SmsModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendSms', 'getEntity'])
            ->getMock();

        $smsModel->method('sendSms')
            ->willReturn(true);
        $smsModel->method('getEntity')
            ->willReturn($sms);

        $transportChain = $this->createMock(TransportChain::class);

        $event    = new Event();
        $campaign = new class() extends Campaign {
            public function getId()
            {
                return 111;
            }
        };
        $leadLog = new class() extends LeadEventLog {
            public function getId()
            {
                return 456;
            }
        };
        $contact = new class() extends Lead {
            public function getId()
            {
                return 789;
            }
        };

        $leadLog->setLead($contact);

        $translator = new class() extends Translator {
            public function __construct()
            {
            }
        };

        $subscriber = new CampaignSendSubscriber(
            $smsModel,
            $transportChain,
            $translator
        );

        $event->setProperties(['sms' => 1]);
        $event->setCampaign($campaign);

        $pendingEvent = new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$leadLog->getId() => $leadLog]));

        $this->assertCount(1, $pendingEvent->getContacts());
        $subscriber->onCampaignTriggerBatchAction($pendingEvent);
    }
}
