<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\EventListener\CampaignSendSubscriber;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignSendSubscriberTest extends TestCase
{
    private PendingEvent $pendingEvent;

    private LeadEventLog $leadLog;

    /**
     * @var MockObject|SmsModel
     */
    private MockObject $smsModel;

    /**
     * @var MockObject|TransportChain
     */
    private MockObject $transportChain;

    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;
    private CampaignSendSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->smsModel       = $this->createMock(SmsModel::class);
        $this->transportChain = $this->createMock(TransportChain::class);
        $this->translator     = $this->createMock(TranslatorInterface::class);

        $lead = new Lead();
        $lead->setId(1);
        $event = new Event();
        $event->setProperties(['sms' => 1]);

        $this->leadLog = new class extends LeadEventLog {
            public function getId(): int
            {
                return 456;
            }
        };

        $this->leadLog->setLead($lead);
        $this->pendingEvent = new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$this->leadLog->getId() => $this->leadLog]));

        $this->subscriber = new CampaignSendSubscriber(
            $this->smsModel,
            $this->transportChain,
            $this->translator
        );
    }

    public function testSendDeletedSms(): void
    {
        $this->smsModel->method('getEntity')
            ->willReturn(null);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.sms.campaign.failed.missing_entity');

        $this->subscriber->onCampaignTriggerBatchAction($this->pendingEvent);

        self::assertTrue((bool) $this->leadLog->getMetadata()['failed']);
    }

    public function testSendUnpublishedSms(): void
    {
        $sms = new Sms();
        $sms->setIsPublished(false);
        $this->smsModel->expects(self::once())->method('getEntity')->willReturn($sms);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.sms.campaign.failed.unpublished');

        $this->subscriber->onCampaignTriggerBatchAction($this->pendingEvent);

        self::assertTrue((bool) $this->leadLog->getMetadata()['failed']);
    }

    public function testOnCampaignTriggerBatchAction(): void
    {
        $sms = $this->createMock(Sms::class);
        $sms->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $sms->expects($this->any())
            ->method('isPublished')
            ->willReturn(true);

        $this->smsModel->method('sendSms')
            ->willReturn([true]);
        $this->smsModel->method('getEntity')
            ->willReturn($sms);

        $this->assertCount(1, $this->pendingEvent->getContacts());
        $this->subscriber->onCampaignTriggerBatchAction($this->pendingEvent);
    }
}
