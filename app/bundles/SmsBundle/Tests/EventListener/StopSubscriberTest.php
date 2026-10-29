<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\EventListener\StopSubscriber;

final class StopSubscriberTest extends TestCase
{
    /**
     * @var MockObject&DoNotContactModel
     */
    private MockObject $doNotContactModel;

    protected function setUp(): void
    {
        $this->doNotContactModel = $this->createMock(DoNotContactModel::class);
    }

    public function testLeadAddedToDNC(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $event = new ReplyEvent($lead, 'stop');

        $this->doNotContactModel->expects($this->once())
        ->method('addDncForContact')
        ->with(1, 'sms', DoNotContact::UNSUBSCRIBED);

        $this->StopSubscriber()->onReply($event);
    }

    private function StopSubscriber(): StopSubscriber
    {
        return new StopSubscriber($this->doNotContactModel);
    }
}
