<?php

namespace Mautic\SmsBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\EventListener\CampaignSendSubscriber;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\MockObject\MockObject;

class CampaignSendSubscriberTest extends \PHPUnit\Framework\TestCase
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
}
